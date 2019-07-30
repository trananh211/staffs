<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Automattic\WooCommerce\Client;
//use File;
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

    /*Create new order*/
    public function createOrder($data, $woo_id)
    {
        $db = array();
        logfile('=====================CREATE NEW ORDER=======================');
//        echo "<pre>";
//        print_r($data);
        $lst_product_skip = $this->getProductSkip();
        if (sizeof($data['line_items']) > 0) {
            logfile('Store ' . $woo_id . ' has new ' . sizeof($data['line_items']) . ' order item.');
            $woo_infos = $this->getWooSkuInfo();
            $lst_product = array();
            foreach ($data['line_items'] as $key => $value) {
                $str = "";
                /*if (in_array($data['status'], array('failed', 'cancelled'))) {
                    continue;
                }*/
                foreach ($value['meta_data'] as $item) {
                    $str .= $item['key'] . " : " . $item['value'] . " -;-;-\n";
                }
                $db[] = [
                    'woo_info_id' => $woo_id,
                    'order_id' => $data['id'],
                    'number' => $data['number'],
                    'order_status' => $data['status'],
                    'status' => $this->getStatusOrder($value['product_id'], $lst_product_skip),
                    'product_id' => $value['product_id'],
                    'product_name' => $value['name'],
                    'sku' => $this->getSku($woo_infos[$woo_id], $value['product_id'], $value['name']),
                    'sku_number' => $this->getSku('', $data['number'], $value['name']),
                    'quantity' => $value['quantity'],
                    'payment_method' => $data['payment_method_title'],
                    'customer_note' => trim(htmlentities($data['customer_note'])),
                    'transaction_id' => $data['transaction_id'],
                    'price' => $value['price'],
                    'variation_id' => $value['variation_id'],
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
        $lists = \DB::table('woo_orders')
            ->join('woo_infos', 'woo_orders.woo_info_id', '=', 'woo_infos.id')
            ->select(
                'woo_orders.id', 'woo_orders.woo_info_id', 'woo_orders.order_id', 'woo_orders.order_status',
                'woo_infos.url', 'woo_infos.consumer_key', 'woo_infos.consumer_secret'
            )
            ->where('woo_orders.status', env('STATUS_NOTFULFILL'))
            ->get();
        if (sizeof($lists) > 0) {
            \DB::beginTransaction();
            try {
                foreach ($lists as $list) {
                    $woocommerce = $this->getConnectStore($list->url, $list->consumer_key, $list->consumer_secret);
                    $info = $woocommerce->get('orders/' . $list->order_id);
                    if ($info && $list->order_status !== $info->status) {
                        \DB::table('woo_orders')->where('id', $list->id)
                            ->update([
                                'order_status' => $info->status,
                                'status' => env('STATUS_WORKING_DONE')
                            ]);
                    }
                }
                $return = true;
                \DB::commit(); // if there was no errors, your query will be executed
            } catch (\Exception $e) {
                $return = false;
                \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            }
            logfile('Đã kiểm tra check payment');
        } else {
            logfile('Check Payment không tìm thấy pending');
        }
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

    private static function getSku($woo_sku, $product_id, $product_name)
    {
        /*Tach product name*/
        $product_name = preg_replace('/\s+/', '', $product_name);
        $tmp = explode('-', $product_name);
        if (sizeof($tmp) > 1) {
            $tmp[0] = (strlen($woo_sku) > 0) ? $woo_sku . '-' . $product_id : $product_id;
            $sku = implode('-', $tmp);
        } else {
            $sku = (strlen($woo_sku) > 0) ? $woo_sku . '-' . $product_id : $product_id;
        }
        return $sku;
    }

    /*
     * Kiem tra template da ton tai hay chua. Neu chua thi luu vao database
     * */
    public function checkTemplate($request)
    {
        try {
            $rq = $request->all();
            $template_id = $rq['id_product'];
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
                //xoa cac key khong can thiet
                $deleted = array('id', 'slug', 'permalink', 'price_html', 'categories', 'images', '_links');
                $variation_list = $template_data['variations'];
                foreach ($deleted as $v) {
                    unset($template_data[$v]);
                }
                //tao thu muc de luu template
                $path = public_path() . '/template/' . $id_store . '/' . $template_id . '/';
                makeFolder($path);
                // Write File
                $template_path = $path . 'temp_' . $template_id . '.json';
                $result = writeFileJson($template_path, $template_data);
                // Nếu tạo file json thành công. Luu thông tin template vao database
                if ($result) {
                    $woo_template_id = \DB::table('woo_templates')->insertGetId([
                        'template_id' => $template_id,
                        'store_id' => $id_store,
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
            return view("/admin/woo/save_path_template", compact('data', "template_data", 'rq'));
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function autoUploadProduct()
    {
        $result_check_category = $this->checkCategory();
        if ($result_check_category) {
            $check_create_product = $this->checkCreateProduct();
        }
    }

    /*Tao moi product*/
    private function checkCreateProduct()
    {
//        echo "<pre>";
//        try {
        logfile('===========[Create Product] =============');
        //kiểm tra xem có file nào đang up dở hay không
        $check_processing = \DB::table('woo_product_drivers')->select('name', 'template_id')->where('status', 1)->first();
        //nếu không có file nào đang up dở
        if ($check_processing == NULL) {
            $limit = 2;
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
            print_r($check);
            if (sizeof($check) > 0) {
                $prod_data = array();
                foreach ($check as $val) {
                    // Tìm template
                    $template_json = readFileJson($val->template_path);
                    $prod_data = $template_json;
                    $prod_data['name'] = ucwords($val->name) . ' ' . $template_json['name'];
                    $prod_data['status'] = 'publish';
                    $prod_data['categories'] = [
                        ['id' => $val->woo_category_id]
                    ];
                    unset($prod_data['variations']);
                    // End tìm template

                    // Tìm image
                    $scan_images = scanGoogleDir($val->path, 'file');
                    $tmp_images = array();
                    $image_local = array();
                    foreach ($scan_images as $file) {
                        $imageFileType = strtolower($file['extension']);
                        if (!in_array($imageFileType, array('jpg', 'jpeg', 'png', 'gif'))) {
                            continue;
                        }
                        if (strpos($file['name'], 'mc') !== false || strpos($file['name'], 'mk') !== false) {
                            //down file về để up lên wordpress
                            $rawData = Storage::cloud()->get($file['path']);
                            $tmp_path = 'img_google/' . $val->name . '/' . $file['name'];
                            if (Storage::disk('public')->put($tmp_path, $rawData)) {
                                $local_path_image = storage_path('app/public/'.$tmp_path);
                                $image_local[] = $local_path_image;
                                $tmp_images[] = [
                                    'src' => $local_path_image
                                ];
                            }
                        }
                    }
//                    print_r($image_local);
                    $prod_data['images'] = $tmp_images;
                    // End tìm image
                    //Kết nối với woocommerce
                    $woocommerce = $this->getConnectStore($val->url, $val->consumer_key, $val->consumer_secret);
                    $save_product = ($woocommerce->post('products', $prod_data));
                    $product_id = $save_product->id;
//                    $variation_data = array();
//                    for ($i = 0; $i < sizeof($info['variations']); $i++) {
//                        $vari = $info['variations'][$i];
//                        $tmp = ($woocommerce->get('products/22/variations/' . $vari));
//                        $variation_data[] = [
//                            'sale_price' => '25.99',
//                            'regular_price' => '30.99',
//                            'attributes' => json_decode(json_encode($tmp->attributes), True)
//                        ];
//                    }
//                    foreach ($variation_data as $value) {
//                        print_r($woocommerce->post('products/' . $product_id . '/variations', $value));
//                    }
                }

            } else {
                logfile('-- Đã hết product để tạo mới.');
            }
        } else {
            echo('[Bỏ qua] Hiện đang tạo product : "' . $check_processing->name . '" có template_id :' . $check_processing->template_id);
        }
//        } catch (\Exception $e) {
//            return $e->getMessage();
//        }

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
        return $result;
    }

    private function temp($rq)
    {
//        echo "<pre>";
        $woocommerce = $this->getConnectStore($rq['url'], $rq['consumer_key'], $rq['consumer_secret']);
        $i = $woocommerce->get('products/' . $rq['id_product']);
        $info = json_decode(json_encode($i), True);

        $folder = 'Test';
        $prod_data = [
            'name' => $folder . ' ' . $info['name'],
            'type' => 'variable',
            'categories' => [
                ['id' => 108]
            ],
            'description' => $info['description'],
            'attributes' => $info['attributes'],
            'tags' => $info['tags'],
            'images' => [
                [
                    'src' => 'https://image.shutterstock.com/image-photo/white-transparent-leaf-on-mirror-260nw-577160911.jpg'
                ],
                [
                    'src' => 'https://cdn.arstechnica.net/wp-content/uploads/2016/02/5718897981_10faa45ac3_b-640x624.jpg'
                ],
                [
                    'src' => 'https://www.ctvnews.ca/polopoly_fs/1.4037876!/httpImage/image.jpg_gen/derivatives/landscape_1020/image.jpg'
                ],
                [
                    'src' => 'https://static.addtoany.com/images/dracaena-cinnabari.jpg'
                ]
            ]
        ];
        print_r($prod_data);
        $save_product = ($woocommerce->post('products', $prod_data));
        $product_id = $save_product->id;
        $variation_data = array();
        for ($i = 0; $i < sizeof($info['variations']); $i++) {
            $vari = $info['variations'][$i];
            $tmp = ($woocommerce->get('products/22/variations/' . $vari));
            $variation_data[] = [
                'sale_price' => '25.99',
                'regular_price' => '30.99',
                'attributes' => json_decode(json_encode($tmp->attributes), True)
            ];
        }
        foreach ($variation_data as $value) {
            print_r($woocommerce->post('products/' . $product_id . '/variations', $value));
        }
        /*$data = [
            'slug' => 'dresses',
        ];
        print_r($woocommerce->get('products/categories',$data));*/
    }
    /*End WooCommerce API*/
}
