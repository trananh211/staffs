<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Automattic\WooCommerce\Client;
use File;
use App\Services\PayUService\Exception;
use Storage;

class Api extends Model
{
    public function log($str)
    {
        \Log::info($str);
    }

    /*WooCommerce API*/
    protected function getConnectStore($url, $consumer_key, $consumer_secret)
    {
        $woocommerce = new Client(
            $url,
            $consumer_key,
            $consumer_secret,
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true,
                'verify_ssl' => false
            ]
        );
        return $woocommerce;
    }

    /*Check order with platform*/
    private static function getProductSkip()
    {
        return \DB::table('woo_products')->where('type', '!=', 0)->pluck('type', 'product_id')->toArray();
    }

    private static function getStatusOrder($product_id, $array)
    {

        $status = env('STATUS_WORKING_NEW');
        if (sizeof($array) > 0 && array_key_exists($product_id, $array)) {
            if ($array[$product_id] == env('TYPE_APP')) {
                $status = env('STATUS_SKIP');
            } else if ($array[$product_id] == env('TYPE_NORMAL')) {
                $status = env('STATUS_PRODUCT_NORMAL');
            }
        }
        return $status;
    }

    private function getPaypalId($woo_id)
    {
        $check_paypal = \DB::table('paypals')
            ->select('id')
            ->where('store_id', $woo_id)
            ->where('status', 1)
            ->first();
        $paypal_id = 0;
        if ($check_paypal) {
            $paypal_id = $check_paypal->id;
        }
        return $paypal_id;
    }

    /*Create new order*/
    public function createOrder($data, $woo_id)
    {
        $db = array();
        logfile('=====================CREATE NEW ORDER=======================');
//        dd($data);
        $lst_product_skip = $this->getProductSkip();
        if (sizeof($data['line_items']) > 0) {
            logfile('Store ' . $woo_id . ' has new ' . sizeof($data['line_items']) . ' order item.');
            $woo_infos = $this->getWooSkuInfo();
            $paypal_id = $this->getPaypalId($woo_id);
            $lst_product = array();
            $check_exist_product_auto = \DB::table('woo_product_drivers')
                ->where('store_id', $woo_id)
                ->where('status', 3)
                ->pluck('woo_product_id')
                ->toArray();
            foreach ($data['line_items'] as $key => $value) {
                $str = "";
                $variation_detail = '';
                $variation_full_detail = '';
                /*if (in_array($data['status'], array('failed', 'cancelled'))) {
                    continue;
                }*/
                $custom_status = env('STATUS_P_DEFAULT_PRODUCT');
                $str_sku = '';
                if (in_array($value['product_id'], $check_exist_product_auto)) {
                    $custom_status = env('STATUS_P_AUTO_PRODUCT');
                }
                foreach ($value['meta_data'] as $item) {
                    if (!is_array($item['value']) && strpos(strtolower($item['key']), 'id_add') === false) {
                        if (strpos(strtolower($item['key']), 'add') !== false) {
                            $str_sku .= ' ' . $item['value'];
                            $custom_status = env('STATUS_P_CUSTOM_PRODUCT');
                        } else {
                            $variation_detail .= $item['value'] . '-';
                        }
                        $variation_full_detail .= $item['value'] . '-;-;-';
                        $str .= $item['key'] . " : " . $item['value'] . " -;-;-\n";
                    }
                }

                $db[] = [
                    'woo_info_id' => $woo_id,
                    'order_id' => $data['id'],
                    'number' => $data['number'],
                    'order_status' => $data['status'],
                    'status' => $this->getStatusOrder($value['product_id'], $lst_product_skip),
                    'product_id' => $value['product_id'],
                    'product_name' => $value['name'],
                    'sku' => $this->getSku($woo_infos[$woo_id], $value['product_id'], $value['name'], $str_sku),
                    'sku_number' => $this->getSku_number('', $data['number'], $value['name']),
                    'quantity' => $value['quantity'],
                    'payment_method' => trim($data['payment_method_title']),
                    'paypal_id' => (trim(strtolower($data['payment_method_title'])) == 'paypal') ? $paypal_id : 0,
                    'customer_note' => trim(htmlentities($data['customer_note'])),
                    'transaction_id' => $data['transaction_id'],
                    'price' => $value['price'],
                    'variation_id' => $value['variation_id'],
                    'variation_detail' => trim(substr($variation_detail, 0, -1)),
                    'variation_full_detail' => trim($variation_full_detail),
                    'custom_status' => $custom_status,
                    'email' => $data['billing']['email'],
                    'fullname' => $data['shipping']['first_name'] . ' ' . $data['shipping']['last_name'],
                    'address' => (strlen($data['shipping']['address_2']) > 0) ? $data['shipping']['address_1'] . ', ' . $data['shipping']['address_2'] : $data['shipping']['address_1'],
                    'city' => $data['shipping']['city'],
                    'postcode' => $data['shipping']['postcode'],
                    'country' => $data['shipping']['country'],
                    'state' => $data['shipping']['state'],
                    'phone' => $data['billing']['phone'],
                    'detail' => trim(htmlentities($str)),
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                $lst_product[] = $value['product_id'];
            }
        }
        if (sizeof($db) > 0) {
            \DB::beginTransaction();
            try {
                \DB::table('woo_orders')->insert($db);
                $return = true;
                $save = "Save to database successfully";
                \DB::commit(); // if there was no errors, your query will be executed
            } catch (\Exception $e) {
                $return = false;
                $save = "[Error] Save to database error.";
                \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            }
            logfile($save . "\n");
        }

        /*Create new product*/
        $this->syncProduct(array_unique($lst_product), $woo_id);
    }

    public function updateProduct($data, $store_id)
    {
        if (sizeof($data) > 0) {
            logfile("==== Update product ====");
            $product_id = $data['id'];
            $product_name = $data['name'];
            $img = '';
            /*kiem tra ton tai product*/
            $woo_product = \DB::table('woo_products')->select('id')->where('product_id', $product_id)->first();
            if ($woo_product != NULL) {
                if (isset($data['images']) && sizeof($data['images']) > 0) {
                    foreach ($data['images'] as $image) {
                        $img .= $image['src'] . ",";
                    }
                }
                if (strlen($img) > 0) {
                    $update = [
                        'name' => $product_name,
                        'permalink' => $data['permalink'],
                        'image' => substr(trim($img), 0, -1),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                } else {
                    $update = [
                        'name' => $product_name,
                        'permalink' => $data['permalink'],
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                }
                \DB::table('woo_products')->where('id', $woo_product->id)->update($update);

                /*Cap nhat Google Driver*/
                $gg_folder = \DB::table('gg_folders')->select('id', 'name', 'path', 'parent_path')
                    ->where([
                        ['product_id', '=', $product_id],
                        ['level', '=', 1]
                    ])
                    ->first();
                if ($gg_folder != NULL) {
                    $parent_path = $gg_folder->parent_path;
                    $check = checkDirExist($gg_folder->name, $gg_folder->path, $parent_path);
                    if ($check) {
                        $path = renameDir($product_name, $gg_folder->name, $parent_path);
                        echo $path;
                    } else {
                        $path = createDir($product_name, $parent_path);
                    }
                    \DB::table('gg_folders')
                        ->where('product_id', $product_id)
                        ->where('level', 1)
                        ->update([
                            'name' => $product_name,
                            'path' => $path,
                            'parent_path' => $parent_path,
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                }
                logfile("Cập nhật thành công product " . $product_name);
            } else {
                logfile("==== Product  " . $product_name . " chưa được mua hàng lần nào. Bỏ qua ====");
            }
        }
    }

    private function syncProduct($lst, $woo_id)
    {
        logfile("==== Create product ====");
        /*Kiem tra xem danh sach product da ton tai hay chua*/
        $products = DB::table('woo_products')
            ->whereIn('product_id', $lst)
            ->where('woo_info_id', $woo_id)
            ->pluck('product_id')
            ->toArray();
        if (sizeof($products) != sizeof($lst)) {
            $lst = array_diff($lst, $products);
            if (sizeof($lst) > 0) {
                $woo_info = DB::table('woo_infos')
                    ->select('url', 'consumer_key', 'consumer_secret')
                    ->where('id', $woo_id)
                    ->get();
                if (sizeof($woo_info) > 0) {
                    $woo_info = json_decode($woo_info, true)[0];
                    $woocommerce = $this->getConnectStore($woo_info['url'], $woo_info['consumer_key'], $woo_info['consumer_secret']);
                    $db = array();
                    foreach ($lst as $product_id) {
                        $data = $woocommerce->get('products/' . $product_id);
                        if ($data) {
                            $img = "";
                            foreach ($data->images as $image) {
                                $img .= $image->src . ",";
                            }
                            $db[] = [
                                'woo_info_id' => $woo_id,
                                'product_id' => $product_id,
                                'name' => $data->name,
                                'permalink' => $data->permalink,
                                'image' => substr(trim($img), 0, -1),
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        }
                    }
                    if (sizeof($db) > 0) {
                        \DB::beginTransaction();
                        try {
                            \DB::table('woo_products')->insert($db);
                            $return = true;
                            $save = "Save " . sizeof($db) . " products to database successfully";
                            \DB::commit(); // if there was no errors, your query will be executed
                        } catch (\Exception $e) {
                            $return = false;
                            $save = "[Error] Save " . sizeof($db) . " product to database error.";
                            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
                        }
                        logfile($save . "\n");
                    }
                }
            }
        } else {
            logfile('All ' . sizeof($lst) . ' products had add to database before.');
        }
    }

    public function checkPaymentAgain()
    {
        logfile('---------------- [Payment Again]------------------');
        $lists = \DB::table('woo_orders')
            ->join('woo_infos', 'woo_orders.woo_info_id', '=', 'woo_infos.id')
            ->select(
                'woo_orders.id', 'woo_orders.woo_info_id', 'woo_orders.order_id', 'woo_orders.order_status',
                'woo_infos.url', 'woo_infos.consumer_key', 'woo_infos.consumer_secret'
            )
            ->where('woo_orders.status', env('STATUS_NOTFULFILL'))
            ->get();
        if (sizeof($lists) > 0) {
            $status = env('STATUS_WORKING_DONE');
            $this->checkPaymentAgain($lists, $status);
        } else {
            logfile('-- [Payment Again] Chuyển sang kiểm tra đơn hàng auto');
//
//            $list_auto = \DB::table('woo_orders')
//                ->join('woo_infos', 'woo_orders.woo_info_id', '=', 'woo_infos.id')
//                ->select(
//                    'woo_orders.id', 'woo_orders.woo_info_id', 'woo_orders.order_id', 'woo_orders.order_status',
//                    'woo_infos.url', 'woo_infos.consumer_key', 'woo_infos.consumer_secret'
//                )
//                ->where('woo_orders.status', env('STATUS_WORKING_NEW'))
//                ->where('woo_orders.custom_status', '<>',env('STATUS_P_CUSTOM_PRODUCT'))
//                ->get();
//            if (sizeof($list_auto) > 0)
//            {
//                $this->checkPaymentAgain($list_auto);
//            } else {
//                logfile('-- [Payment Again] Check Payment không tìm thấy pending');
//            }
            logfile('-- [Payment Again] Check Payment không tìm thấy pending');
        }
    }

    public function updateOrderPaypal()
    {
        $lists = \DB::table('woo_orders')
            ->join('woo_infos', 'woo_orders.woo_info_id', '=', 'woo_infos.id')
            ->select(
                'woo_orders.id', 'woo_orders.woo_info_id', 'woo_orders.order_id', 'woo_orders.order_status',
                'woo_infos.url', 'woo_infos.consumer_key', 'woo_infos.consumer_secret'
            )
            ->where('woo_orders.transaction_id', '')
            ->get();
        if (sizeof($lists) > 0) {
            $this->actionCheckPayment($lists, null);
        }
    }

    private function actionCheckPayment($lists, $status = null)
    {
        \DB::beginTransaction();
        try {
            foreach ($lists as $list) {
                $woocommerce = $this->getConnectStore($list->url, $list->consumer_key, $list->consumer_secret);
                $info = $woocommerce->get('orders/' . $list->order_id);
                if ($info) {
                    if ($status != null && $list->order_status !== $info->status) {
                        $update = [
                            'transaction_id' => $info->transaction_id,
                            'order_status' => $info->status,
                            'status' => $status
                        ];
                    } else {
                        $update = [
                            'transaction_id' => $info->transaction_id,
                            'order_status' => $info->status
                        ];
                    }
                    $result = \DB::table('woo_orders')->where('id', $list->id)->update($update);
                    if ($result) {
                        logfile('-- [Payment Again] Cập nhật thành công ' . $list->number);
                    } else {
                        logfile('-- [Payment Again] [Error] Cập nhật thất bại ' . $list->number);
                    }
                }
            }
            $return = true;
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $return = false;
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        logfile('-- [Payment Again] Đã kiểm tra xong ' . sizeof($lists) . ' check payment');
    }

    public function updateSku()
    {
        $woo_infos = $this->getWooSkuInfo();
        $status = 'error';
        $message = 'Không có thông tin nào về store';
        if (sizeof($woo_infos) > 0) {
            $lists = \DB::table('woo_orders')
                ->select('id', 'woo_info_id', 'product_id', 'product_name', 'number')
                ->where('sku_number', '')
                ->get();
            if (sizeof($lists) > 0) {
                \DB::beginTransaction();
                try {
                    foreach ($lists as $list) {
                        $sku = $this->getSku('', $list->number, $list->product_name);
                        \DB::table('woo_orders')
                            ->where('id', $list->id)
                            ->update([
                                'sku_number' => $sku,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                    }
                    $status = 'success';
                    $message = 'Đã update sku cho ' . sizeof($lists) . ' đơn hàng';
                    \DB::commit(); // if there was no errors, your query will be executed
                } catch (\Exception $e) {
                    $status = 'error';
                    $message = 'Xảy ra lỗi. Hãy thử lại.';
                    \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
                }
            } else {
                $status = 'success';
                $message = 'Tất cả các đơn hàng đều đủ thông tin sku';
            }
        }
        return redirect('/woo-webhooks')->with($status, $message);
    }

    public function updateOrder($request)
    {
        $status = '';
        $message = '';
        $data = array();
        $rq = $request->all();
        $woo_id = $rq['id_store'];
        $info = $this->getInfoStore($woo_id);
        if ($info) {
            $woocommerce = $this->getConnectStore($info->url, $info->consumer_key, $info->consumer_secret);
            $list_orders = explode(',', $rq['order_id']);
            foreach ($list_orders as $list_order) {
                $tmp = explode('-', $list_order);
                $woo_order_id = array_pop($tmp);
                if (!is_numeric($woo_order_id)) {
                    $message .= getErrorMessage('Không tồn tại order :' . $list_order);
                    continue;
                } else {
                    $data[] = $woocommerce->get('orders/' . $woo_order_id);
                }
            }
            if (sizeof($data) > 0) {
                foreach ($data as $dt) {
                    $dt = json_encode($dt, true);
                    $dt = (json_decode($dt, true));
                    $this->createOrder($dt, $woo_id);
                }
                $status = 'success';
                $message .= 'Cập nhật thành công';
            }
        } else {
            $status = 'error';
            $message = getErrorMessage('Không tồn tại store này. Mời bạn thử lại');
        }
        return redirect('/list-order')->with($status, $message);
    }

    private function getInfoStore($id)
    {
        return \DB::table('woo_infos')->select('url', 'consumer_key', 'consumer_secret')->where('id', $id)->first();
    }

    private function getWooSkuInfo()
    {
        return \DB::table('woo_infos')->pluck('sku', 'id')->toArray();
    }

    private static function getSku($woo_sku, $product_id, $product_name, $str_sku = null)
    {
        /*Tach product name*/
        $product_name = sanitizer($product_name);
        $str_sku = sanitizer($str_sku);
        $product_name = preg_replace('/\s+/', ' ', $product_name);
        $str_sku = preg_replace('/\s+/', '', ucwords($str_sku));
        $tmp = explode(" ", $product_name);
        if (sizeof($tmp) > 1) {
//            $tmp[0] = (strlen($woo_sku) > 0) ? $woo_sku . '-' . $product_id : $product_id;
//            $sku = implode('-', $tmp);
            if ($str_sku == null) {
                $sku = $tmp[0] . $tmp[sizeof($tmp) - 1];
            } else {
                $sku = $str_sku . $tmp[sizeof($tmp) - 1];
            }
        } else {
            $sku = (strlen($woo_sku) > 0) ? $woo_sku . '-' . $product_id : $product_id;
        }
        return $sku;
    }

    private static function getSku_number($woo_sku, $product_id)
    {
        /*Tach product name*/
        $sku = (strlen($woo_sku) > 0) ? $woo_sku . '-' . $product_id : $product_id;
        return $sku;
    }

    /*
     * Kiem tra template da ton tai hay chua. Neu chua thi luu vao database
     * */
    public function checkTemplate($request, $scrap = null)
    {
        try {
            $rq = $request->all();
            $template_id = $rq['id_product'];
            if ($scrap == 1) {
                $website_id = $rq['website_id'];
            } else {
                $website_id = null;
            }
            $id_store = $rq['id_store'];
            $check_exist = \DB::table('woo_templates')
                ->where('template_id', $template_id)
                ->where('store_id', $id_store)
                ->select('template_path')
                ->first();
            // neu khong ton tai template id trong he thong.
            if (!is_null($check_exist)) {
                $template_path = $check_exist->template_path;
                $template_data = readFileJson($template_path);
            } else {
                $woocommerce = $this->getConnectStore($rq['url'], $rq['consumer_key'], $rq['consumer_secret']);
                $i = $woocommerce->get('products/' . $rq['id_product']);
                $template_data = json_decode(json_encode($i), True);
                $template_name = $template_data['name'];
                $description = htmlentities(str_replace("\n", "<br />", $template_data['description']));
                $template_data['description'] = $description;
                //xoa cac key khong can thiet
                $deleted = array('id', 'slug', 'permalink', 'price_html', 'categories', 'images', '_links');
                $variation_list = $template_data['variations'];
                foreach ($deleted as $v) {
                    unset($template_data[$v]);
                }
                //tao thu muc de luu template
                $path = storage_path('app/public') . '/template/' . $id_store . '/' . $template_id . '/';
                makeFolder(($path));
                // Write File
                $template_path = $path . 'temp_' . $template_id . '.json';
                $result = writeFileJson($template_path, $template_data);
                chmod($template_path, 777);
                // Nếu tạo file json thành công. Luu thông tin template vao database
                if ($result) {
                    logfile('-- Tạo json file template thành công. chuyển sang tạo variantions file json');
                    $woo_template_id = \DB::table('woo_templates')->insertGetId([
                        'product_name' => $template_name,
                        'template_id' => $template_id,
                        'store_id' => $id_store,
                        'website_id' => $website_id,
                        'template_path' => $template_path,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    // Quét thông tin variations gửi vào database
                    $insert_variation = array();
                    for ($i = 0; $i < sizeof($variation_list); $i++) {
                        $varid = $variation_list[$i];
                        $variation_path = $path . 'variation_' . $varid . '.json';
                        $variation_data = $woocommerce->get('products/' . $template_id . '/variations/' . $varid);
                        $result = writeFileJson($variation_path, $variation_data);
                        if ($result) {
                            logfile('-- Tạo json file variations thành công. ' . $variation_path);
                        }
                        chmod($variation_path, 0777);
                        $insert_variation[] = [
                            'variation_id' => $varid,
                            'woo_template_id' => $woo_template_id,
                            'template_id' => $template_id,
                            'store_id' => $id_store,
                            'variation_path' => $variation_path,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                    }
                    if (sizeof($insert_variation) > 0) {
                        \DB::table('woo_variations')->insert($insert_variation);
                    }
                }
            }
            $data = array();
            if ($scrap != null) {
                return redirect('scrap-create-template')->with('success', 'Connect với template thành công');
            } else {
                return view("/admin/woo/save_path_template", compact('data', "template_data", 'rq'));
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function autoUploadProduct()
    {
        $result_check_category = $this->checkCategory();
        if ($result_check_category) {
            $this->checkCreateProduct();
        }
    }

    public function autoUploadImage()
    {
        try {
            $limit = 2;
            $check = \DB::table('woo_product_drivers')
                ->where('status', 1)
                ->orderBy('id', 'ASC')
                ->orderBy('store_id', 'ASC')
                ->pluck('id', 'woo_product_id');
            if (sizeof($check) > 0) {
                $checks = \DB::table('woo_image_uploads as woo_up')
                    ->leftjoin('woo_product_drivers as wpd', 'wpd.id', '=', 'woo_up.woo_product_driver_id')
                    ->leftjoin('woo_infos as woo_info', 'wpd.store_id', '=', 'woo_info.id')
                    ->select(
                        'woo_up.id as woo_up_id', 'woo_up.woo_product_driver_id', 'woo_up.url as woo_up_url', 'woo_up.store_id',
                        'wpd.woo_product_id',
                        'woo_info.url', 'woo_info.consumer_key', 'woo_info.consumer_secret'
                    )
                    ->whereIn('woo_up.woo_product_driver_id', $check)
                    ->orderBy('woo_up.id', 'ASC')
                    ->get()->toArray();
                if (sizeof($checks) > 0) {
                    $stores = array();
                    $tmp = array();
                    $tmp_woo_up_id = array();
                    foreach ($checks as $val) {
                        $tmp[$val->woo_product_id][] = [
                            'src' => $val->woo_up_url
//                            'src' => 'https://image.shutterstock.com/image-photo/white-transparent-leaf-on-mirror-260nw-1029171697.jpg'
                        ];

                        $tmp_woo_up_id[$val->woo_product_id][] = $val->woo_up_id;
                        $stores[$val->store_id] = [
                            'url' => $val->url,
                            'consumer_key' => $val->consumer_key,
                            'consumer_secret' => $val->consumer_secret,
                            'images' => $tmp,
                            'woo_up_id' => $tmp_woo_up_id
                        ];
                    }
                    logfile("-- Đang tải " . sizeof($checks) . " images từ store :" . $val->url);
                    $product_update_data = array();
                    $product_image_uploaded = array();
                    $product_update_data_false = array();
                    $slug_data = array();
                    foreach ($stores as $store_id => $store) {
                        $update_images_data = array();
                        $change_status_image = array();
                        $up_id_data = $store['woo_up_id'];
                        //Kết nối với woocommerce
                        $woocommerce = $this->getConnectStore($store['url'], $store['consumer_key'], $store['consumer_secret']);
                        $check_upload_false = true;
                        $i = 0;
                        foreach ($store['images'] as $product_id => $images) {
                            if ($i >= $limit) {
                                break;
                            }
                            $i++;
                            $tmp = array(
                                'id' => $product_id,
                                'status' => 'publish',
                                'images' => $images,
                                'date_created' => date("Y-m-d H:i:s", strtotime(" -6 month"))
                            );
                            $update_images_data['update'][] = $tmp;
                            $result = $woocommerce->put('products/' . $product_id, $tmp);
                            if ($result) {
                                $myarray = (array)$result;
                                $slug_data[$store_id][$product_id] = $myarray['permalink'];
                                $product_update_data[] = $product_id;
                                $product_image_uploaded = array_merge($product_image_uploaded, $store['woo_up_id'][$product_id]);
                                logfile('--- Upload thành công: ' . sizeof($images) . ' của product_id: ' . $product_id);
                            } else {
                                $product_update_data_false[] = $product_id;
                                logfile('--- [Error] Upload thất bại: ' . sizeof($images) . ' của product_id: ' . $product_id);
                            }
                        }
                    }

                    if (sizeof($product_image_uploaded) > 0) {
                        \DB::table('woo_image_uploads')->whereIn('id', $product_image_uploaded)->update(['status' => 1]);
                    }

                    if (sizeof($slug_data) > 0) {
                        foreach ($slug_data as $store_id => $value) {
                            foreach ($value as $woo_product_id => $woo_slug) {
                                \DB::table('woo_product_drivers')
                                    ->where([
                                        ['woo_product_id', '=', $woo_product_id],
                                        ['store_id', '=', $store_id]
                                    ])
                                    ->update([
                                        'woo_slug' => $woo_slug
                                    ]);
                            }
                        }

                        if (sizeof($product_update_data) > 0) {
                            $check = \DB::table('woo_product_drivers as wpd')
                                ->join('woo_image_uploads as woo_up', 'wpd.id', '=', 'woo_up.woo_product_driver_id')
                                ->whereIn('wpd.woo_product_id', $product_update_data)
                                ->where('woo_up.status', 0)
                                ->orderBy('woo_up.id', 'ASC')
                                ->pluck('wpd.id', 'wpd.woo_product_id')
                                ->toArray();
                            foreach ($product_update_data as $key => $product_id) {
                                if (array_key_exists($product_id, $check)) {
                                    unset($product_update_data[$key]);
                                }
                            }
                            if (sizeof($product_update_data) > 0) {
                                \DB::table('woo_product_drivers')->whereIn('woo_product_id', $product_update_data)->update(['status' => 3]);
                            }
                        }
                    }
                    logfile('-- [END] Hoàn tất tiến trình upload ảnh.');
                } else {
                    logfile('-- [END] Đã hết ảnh để tải lên woocommerce. Kết thúc.');
                    logfile('-- Chuyển sang xóa sản phẩm');
                    $this->deleteProductUploaded();
                }
            }
        } catch (\Exception $e) {
            logfile($e->getMessage());
            return $e->getMessage();
        }

    }

    /*Xóa categories*/
    public function deletedCategories()
    {
        \DB::beginTransaction();
        try {
            $lists = \DB::table('woo_categories as woc')
                ->join('woo_infos', 'woc.store_id', '=', 'woo_infos.id')
                ->select(
                    'woc.id', 'woc.woo_category_id', 'woc.store_id',
                    'woo_infos.url', 'woo_infos.consumer_key', 'woo_infos.consumer_secret'
                )
                ->where('woc.status', 23)
                ->limit(100)
                ->get()->toArray();
            logfile('-- Đang xóa ' . sizeof($lists) . ' categories');
            if (sizeof($lists) > 0) {
                $data = array();
                foreach ($lists as $list) {
                    $data[$list->store_id]['url'] = $list->url;
                    $data[$list->store_id]['consumer_key'] = $list->consumer_key;
                    $data[$list->store_id]['consumer_secret'] = $list->consumer_secret;
                    $data[$list->store_id]['data'][] = $list->woo_category_id;
                    $data[$list->store_id]['data_update'][] = $list->id;
                }
                foreach ($data as $db) {
                    $deleted['delete'] = $db['data'];
                    //Kết nối với woocommerce
                    $woocommerce = $this->getConnectStore($db['url'], $db['consumer_key'], $db['consumer_secret']);
                    $result_delete = $woocommerce->post('products/categories/batch', $deleted);
                    if ($result_delete) {
                        \DB::table('woo_categories')->whereIn('id', $db['data_update'])->update(['status' => 24]);
                    }
                }
            } else {
                $str = '-- Đã hết category để xóa.';
                echo $str;
                logfile($str);
            }
            $return = true;
            $save = "Save to database successfully";
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $return = false;
            $save = "[Error] Save to database error.";
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
    }

    /*Xóa product*/
    private function deleteProductUploaded()
    {
        $temps = \DB::table('woo_templates')->select('id', 'template_id', 'store_id', 'website_id')->where('status', 23)->first();
        if ($temps != NULL) {
            $where = [
                ['template_id', '=', $temps->template_id],
                ['store_id', '=', $temps->store_id]
            ];
            $limit = 15;
            if ($temps->website_id != NULL) // sản phẩm cần xóa ở bản scrap_product
            {
                $products = \DB::table('scrap_products')
                    ->select('id', 'woo_product_id')
                    ->where($where)
                    ->limit($limit)
                    ->get()->toArray();
            } else { // sản phẩm cần xóa ở bảng woo_product_drivers
                $products = \DB::table('woo_product_drivers')
                    ->select('id', 'woo_product_id')
                    ->where($where)
                    ->limit($limit)
                    ->get()->toArray();
            }
            $woo_infos = \DB::table('woo_infos as woo_info')
                ->select('woo_info.url', 'woo_info.consumer_key', 'woo_info.consumer_secret')
                ->where('id', $temps->store_id)->first();
            if (sizeof($products) > 0) {
                $delete = array();
                $update_deleted = array();
                foreach ($products as $value) {
                    $update_deleted[] = $value->id;
                    $delete[] = $value->woo_product_id;
                }
                $data['delete'] = $delete;
                //Kết nối với woocommerce
                $woocommerce = $this->getConnectStore($woo_infos->url, $woo_infos->consumer_key, $woo_infos->consumer_secret);
                $result_delete = $woocommerce->post('products/batch', $data);
                if ($result_delete) {
                    if ($temps->website_id != NULL) // sản phẩm cần xóa ở bản scrap_product
                    {
                        \DB::table('scrap_products')->whereIn('id', $update_deleted)->delete();
                    } else { // sản phẩm cần xóa ở bảng woo_product_drivers
                        \DB::table('woo_product_drivers')->whereIn('id', $update_deleted)->delete();
                    }
                    logfile('-- [Success] Đã xóa ' . sizeof($delete) . ' sản phẩm thành công.');
                } else {
                    logfile('-- [Error] Không thể xóa sản phẩm thuộc template= ' . $temps->template_id . ' và store: ' . $temps->store_id);
                }
            } else {
                \DB::table('woo_templates')->where('id', $temps->id)->update(['status' => 24]);
                logfile('-- Đã hết sản phẩm để xóa.');
            }
        } else {
            logfile('-- [End] Đã hết template để xóa.');
        }
    }

    /*Tao moi product*/
    private function checkCreateProduct()
    {
        try {
            logfile('===============[Create Product] =============');
            //kiểm tra xem có file nào đang up dở hay không
            $check_processing = \DB::table('woo_product_drivers')->select('name', 'template_id')->where('status', 2)->first();
            //nếu không có file nào đang up dở
            if ($check_processing == NULL) {
                $limit = 5;
                $check = \DB::table('woo_product_drivers as wopd')
                    ->join('woo_categories as woo_cat', 'wopd.woo_category_id', '=', 'woo_cat.id')
                    ->join('woo_infos as woo_info', 'wopd.store_id', '=', 'woo_info.id')
                    ->join('woo_templates as woo_temp', function ($join) {
                        $join->on('wopd.template_id', '=', 'woo_temp.template_id');
                        $join->on('wopd.store_id', '=', 'woo_temp.store_id');
                    })
                    ->select(
                        'wopd.id as woo_product_driver_id', 'wopd.name', 'wopd.path', 'wopd.template_id', 'wopd.store_id',
                        'woo_cat.woo_category_id', 'woo_temp.template_path',
                        'woo_info.url', 'woo_info.consumer_key', 'woo_info.consumer_secret'
                    )
                    ->where([
                        ['wopd.status', '=', 0]
                    ])
                    ->orderBy('wopd.created_at', 'ASC')
                    ->limit($limit)
                    ->get()->toArray();
                if (sizeof($check) > 0) {
                    $stores_process = array();
                    $image_local = array();
                    foreach ($check as $val) {
                        $prod_data = array();
                        // Tìm template
                        $template_json = readFileJson($val->template_path);
                        $woo_product_name = ucwords($val->name) . ' ' . $template_json['name'];
                        logfile("-- Đang tạo sản phẩm mới : " . $woo_product_name);
                        $prod_data = $template_json;
                        $prod_data['name'] = ucwords($val->name) . ' ' . $template_json['name'];
                        $prod_data['status'] = 'draft';
                        $prod_data['categories'] = [
                            ['id' => $val->woo_category_id]
                        ];
                        $prod_data['description'] = html_entity_decode($template_json['description']);
                        unset($prod_data['variations']);
                        // End tìm template

                        // Tìm image
                        $scan_images = scanGoogleDir($val->path, 'file');
//                        dd($scan_images);
                        logfile('---- Tìm được ' . sizeof($scan_images) . ' ảnh của ' . ucwords($val->name));
                        $tmp_images = array();
                        $woo_product_driver_id_array = array();
                        $m = 0;
                        foreach ($scan_images as $file) {
                            $imageFileType = strtolower($file['extension']);
                            if (!in_array($imageFileType, array('jpg', 'jpeg', 'png', 'gif'))) {
                                continue;
                            }
                            if (strpos($file['name'], 'mc') !== false || strpos($file['name'], 'mk') !== false) {
                                $m++;
                                //down file về để up lên wordpress
                                $rawData = Storage::cloud()->get($file['path']);
                                $tmp_path = 'img_google/' . $val->name . '/' . $file['name'];
                                $local_path_image_public = public_path($tmp_path);
                                makeFolder(dirname($local_path_image_public));
//                                chmod(dirname($local_path_image_public), 0777);
                                if (Storage::disk('public')->put($tmp_path, $rawData)) {
                                    $local_path_image = storage_path('app/public/' . $tmp_path);
                                    makeFolder(dirname($local_path_image_public));
//                                    chmod($local_path_image, 777);
                                    File::move($local_path_image, $local_path_image_public);
                                    $image_local[] = [
                                        'woo_product_driver_id' => $val->woo_product_driver_id,
                                        'path' => $local_path_image_public,
                                        'url' => env('URL_LOCAL') . $tmp_path,
                                        'store_id' => $val->store_id,
                                        'status' => 0
                                    ];
                                    logfile('----- Chấp nhận file ' . $file['name']);
                                }
                            }
                        }
                        logfile('---- Tìm được ' . $m . ' ảnh của ' . ucwords($val->name));
                        // $prod_data['images'] = $tmp_images;

                        // End tìm image
                        //Kết nối với woocommerce
                        $woocommerce = $this->getConnectStore($val->url, $val->consumer_key, $val->consumer_secret);
                        $save_product = ($woocommerce->post('products', $prod_data));
                        $woo_product_id = $save_product->id;
                        // Cap nhat product id vao woo_product_driver
                        \DB::table('woo_product_drivers')->where('id', $val->woo_product_driver_id)
                            ->update([
                                'woo_product_id' => $woo_product_id,
                                'woo_product_name' => $woo_product_name,
                                'status' => 1,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                        $woo_product_driver_id_array[] = $val->woo_product_driver_id;
                        $stores_process[] = array(
                            'url' => $val->url,
                            'consumer_key' => $val->consumer_key,
                            'consumer_secret' => $val->consumer_secret,
                            'variations' => $woo_product_driver_id_array
                        );
                    }

                    //Cập nhật variations vào product id
                    if (sizeof($stores_process) > 0) {
                        logfile('-- Cập nhật variations vào product');
                        foreach ($stores_process as $store) {
                            $lst_prod_variations = array();
                            $lst_prod_variations = \DB::table('woo_product_drivers as wpd')
                                ->join('woo_variations as woo_vari', 'wpd.template_id', '=', 'woo_vari.template_id')
                                ->select(
                                    'wpd.name', 'wpd.woo_product_id',
                                    'woo_vari.variation_id', 'woo_vari.variation_path'
                                )
                                ->whereIn('wpd.id', $store['variations'])
                                ->orderBy('wpd.id', 'ASC')
                                ->get()->toArray();
                            if (sizeof($lst_prod_variations) > 0) {
                                $woocommerce = $this->getConnectStore($store['url'], $store['consumer_key'], $store['consumer_secret']);
                                foreach ($lst_prod_variations as $variation) {
                                    $variation_json = readFileJson($variation->variation_path);
                                    $variation_data = array(
                                        'price' => $variation_json['price'],
                                        'regular_price' => $variation_json['regular_price'],
                                        'sale_price' => $variation_json['sale_price'],
                                        'status' => $variation_json['status'],
                                        'attributes' => $variation_json['attributes'],
                                        'menu_order' => $variation_json['menu_order'],
                                        'meta_data' => $variation_json['meta_data'],
                                    );
                                    $woocommerce->post('products/' . $variation->woo_product_id . '/variations', $variation_data);
//                                    logfile('-- Đang cập nhật variation của '.$variation->woo_product_id);
                                }
                            }
                        }
                    }
                    if (sizeof($image_local) > 0) {
                        \DB::table('woo_image_uploads')->insert($image_local);
                    }
                    logfile('-- [END] Hoàn tất quá trình tạo sản phẩm.');
                } else {
                    logfile('-- [END] Đã hết product để chuẩn bị dữ liệu.');
                }
            } else {
                logfile('[Bỏ qua] Hiện đang tạo product : "' . $check_processing->name . '" có template_id :' . $check_processing->template_id);
            }
        } catch (\HttpClientException $e) {
            return $e->getMessage();
        }

    }

    /*Tao category cap nhat vao file de tao product*/
    private function checkCategory()
    {
        logfile('===============[Check Category]==============');
        $lst_product_category = \DB::table('woo_product_drivers as wpd')
            ->join('woo_infos as woo_info', 'wpd.store_id', '=', 'woo_info.id')
            ->select(
                'wpd.id as woo_product_driver_id', 'wpd.name', 'wpd.store_id',
                'woo_info.url', 'woo_info.consumer_key', 'woo_info.consumer_secret'
            )
            ->where([
                ['woo_category_id', '=', NULL]
            ])
            ->get()->toArray();
        if (sizeof($lst_product_category) > 0) {
            $category_store_lst = array();
            $tmp = array();
            $woo_product_driver_update = array();
            // cập nhật category_id vào woo_product_drivers
            $categories = \DB::table('woo_categories')
                ->select('id', 'store_id', 'slug')
                ->get()->toArray();
            // tạo mảng mới có key là store_id và name folder để so sánh
            $compare_categories = array();
            foreach ($categories as $category) {
                $key = $category->store_id . '_' . $category->slug;
                $compare_categories[$key] = $category->id;
            }
            foreach ($lst_product_category as $val) {
                $key_compare = $val->store_id . '_' . $val->name;
                //nếu đã tồn tại
                if (array_key_exists($key_compare, $compare_categories)) {
                    $woo_product_driver_update[$compare_categories[$key_compare]][] = $val->woo_product_driver_id;
                } else { // nếu chưa tồn tại. lưu vào 1 mảng khác để truy vấn.
                    $tmp[] = $val->name;
                    $category_store_lst[$val->store_id] = [
                        'url' => $val->url,
                        'consumer_key' => $val->consumer_key,
                        'consumer_secret' => $val->consumer_secret,
                        'categories' => $tmp
                    ];
                }
            }
            //nếu tồn tại sản phẩm chưa có category
            if (sizeof($category_store_lst) > 0) {
                $woo_categories_data = array();
                foreach ($category_store_lst as $store_id => $info) {
                    $woocommerce = $this->getConnectStore($info['url'], $info['consumer_key'], $info['consumer_secret']);
                    foreach ($info['categories'] as $category_name) {
                        $data = [
                            'slug' => $category_name,
                        ];
                        // kết nối tới woocommerce store để lấy thông tin
                        $result = ($woocommerce->get('products/categories', $data));
                        //nếu không thấy thông tin thì tạo mới
                        if (sizeof($result) == 0) {
                            $data = [
                                'name' => $category_name
                            ];
                            $i = ($woocommerce->post('products/categories', $data));
                            $woo_categories_data[] = [
                                'woo_category_id' => $i->id,
                                'name' => $i->name,
                                'slug' => $i->slug,
                                'store_id' => $store_id,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        } else {
                            $woo_categories_data[] = [
                                'woo_category_id' => $result[0]->id,
                                'name' => $result[0]->name,
                                'slug' => $result[0]->slug,
                                'store_id' => $store_id,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        }
                    }
                }
                //them toan bo thong tin woo_categories mới get được về database
                if (sizeof($woo_categories_data) > 0) {
                    logfile('-- Tạo mới thông tin woo_categories : ' . sizeof($woo_categories_data) . ' news');
                    \DB::table('woo_categories')->insert($woo_categories_data);
                }
            }

            // Nếu tồn tại thông tin để update vào sản phẩm lưu driver
            if (sizeof($woo_product_driver_update) > 0) {
                logfile('-- Cập nhật thông tin category vào woo_product_driver : ' . sizeof($woo_product_driver_update) . ' update.');
                foreach ($woo_product_driver_update as $woo_category_id => $list_id) {
                    $data = [
                        'woo_category_id' => $woo_category_id
                    ];
                    \DB::table('woo_product_drivers')->whereIn('id', $list_id)->update($data);
                }
            }
            $result = false;
        } else {
            $result = true;
            logfile('-- Đã cập nhật đủ category. Chuyển sang tạo product');
        }
        $result = true;
        return $result;
    }
    /*End WooCommerce API*/
}
