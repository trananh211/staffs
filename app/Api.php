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
            $tmp_orders = array();
            foreach ($data['line_items'] as $key => $value) {
                if (!in_array($data['number'], $tmp_orders))
                {
                    $shipping_cost = $data['shipping_total'];
                    $tmp_orders[] = $data['number'];
                } else {
                    $shipping_cost = 0;
                }
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
                    if (!is_array($item['value']) && strpos(strtolower($item['key']), '_id_') === false) {
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
                if (strpos(($value['name']), "-") !== false) {
                    $value['name'] = trim(explode("-", $value['name'])[0]);
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
                    'shipping_cost' => $shipping_cost,
                    'variation_id' => $value['variation_id'],
                    'variation_detail' => trim(substr($variation_detail, 0, -1)),
                    'variation_full_detail' => trim($variation_full_detail),
                    'custom_status' => $custom_status,
                    'email' => $data['billing']['email'],
                    'last_name' => trim($data['shipping']['last_name']),
                    'first_name' => trim($data['shipping']['first_name']),
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

        /*get designs SKU*/
        $this->getDesignNew();
    }

    public function updateOrderWoo($data, $woo_id)
    {
        $return = false;
        if (is_array($data) && array_key_exists('id', $data)) {
            \DB::beginTransaction();
            try {
                $order_id = $data['id'];
                $check_exist = \DB::table('woo_orders')->select('id')
                    ->where('order_id', $order_id)->where('woo_info_id', $woo_id)->first();
                if ($check_exist != NULL) {
                    $update = [
                        'order_status' => $data['status'],
                        'customer_note' => trim(htmlentities($data['customer_note'])),
                        'email' => $data['billing']['email'],
                        'fullname' => $data['shipping']['first_name'] . ' ' . $data['shipping']['last_name'],
                        'address' => (strlen($data['shipping']['address_2']) > 0) ? $data['shipping']['address_1'] . ', ' . $data['shipping']['address_2'] : $data['shipping']['address_1'],
                        'city' => $data['shipping']['city'],
                        'postcode' => $data['shipping']['postcode'],
                        'country' => $data['shipping']['country'],
                        'state' => $data['shipping']['state'],
                        'phone' => $data['billing']['phone'],
                        'transaction_id' => $data['transaction_id'],
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                    $result = \DB::table('woo_orders')
                        ->where('order_id', $order_id)->where('woo_info_id', $woo_id)
                        ->update($update);
                    if ($result) {
                        $return = true;
                    }
                }
                \DB::commit(); // if there was no errors, your query will be executed
            } catch (\Exception $e) {
                $return = false;
                $save = "[Error] Save to database error.";
                \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            }
            return $return;
        }
    }

    public function getDesignNew()
    {
        logfile('== Tạo Design new');
        //lấy danh sách order mới mà chưa có design id
        $list_orders = \DB::table('woo_orders')
            ->select('id', 'product_name', 'product_id', 'woo_info_id', 'sku', 'variation_detail')
            ->where([
                ['status', '=', env('STATUS_WORKING_NEW')]
            ])
            ->whereNull('design_id')
            ->get()->toArray();
        if (sizeof($list_orders) > 0)
        {
            $list_designs = \DB::table('designs')->select('id','sku','variation')->get()->toArray();
            $ar_design = array();
            foreach ($list_designs as $item)
            {
                $key = $item->sku."___".$item->variation;
                $ar_design[$key] = $item->id;
            }

            //so sánh để lưu vào database
            $data_designed = array();
            $data_send_to_staff = array();
            foreach ($list_orders as $value)
            {
                $key = $value->sku."___".$value->variation_detail;
                // nếu đã tồn tại designs thì cập nhật vào woo_orders
                if (array_key_exists($key, $ar_design))
                {
                    $data_designed[$ar_design[$key]][] = $value->id;
                } else  // nếu chưa tồn tại design thì chuyển sang cho staff làm
                {
                    $data_send_to_staff[] = $value->id;
                }
            }
            //lấy ra danh sách variations để thêm thông tin về tool_category_id
            $lst_variations = \DB::table('variations')
                ->pluck('tool_category_id', 'variation_name')
                ->toArray();
            $data_new_variations = array();
            // nếu tồn tại file chuyển cho staff thì cập nhật luôn
            if (sizeof($data_send_to_staff) > 0)
            {
                $ar_orders = array();
                // dồn list order vào chung 1 sku để tạo data cho bảng designs
                foreach ($list_orders as $order)
                {
                    //nếu id tồn tại trong data send to staff
                    if (in_array($order->id, $data_send_to_staff))
                    {
                        $tool_category_id = null;
                        if(array_key_exists(trim($order->variation_detail), $lst_variations))
                        {
                            $tool_category_id = $lst_variations[$order->variation_detail];
                        } else {
                            $data_new_variations[] = [
                                'variation_name' => $order->variation_detail,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        }
                        $key = $order->sku."___".$order->variation_detail;
                        $ar_orders[$key]['info'] = [
                            'product_name' => $order->product_name,
                            'product_id' => $order->product_id,
                            'store_id' => $order->woo_info_id,
                            'sku' => $order->sku,
                            'variation' => $order->variation_detail,
                            'tool_category_id' => $tool_category_id,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $ar_orders[$key]['list_id'][] = $order->id;
                    }
                }
                // bắt đầu tạo data cho design
                if (sizeof($ar_orders) > 0)
                {
                    foreach ($ar_orders as $key => $data)
                    {
                        $design_id = \DB::table('designs')->insertGetId($data['info']);
                        $data_designed[$design_id] = $data['list_id'];
                    }
                }
            }

            // nếu tồn tại file đã design rồi thì cập nhật lại woo_order
            if (sizeof($data_designed) > 0)
            {
                foreach ($data_designed as $design_id => $list_woo_order_id)
                {
                    $result = \DB::table('woo_orders')
                        ->whereIn('woo_orders.id', $list_woo_order_id)
                        ->update([
                            'woo_orders.design_id' => $design_id,
                            'woo_orders.updated_at' => date("Y-m-d H:i:s")
                        ]);
                }
            }

            // nếu phát hiện 1 variation mới. lưu vào variations
            if (sizeof($data_new_variations) > 0)
            {
                $result = \DB::table('variations')->insert($data_new_variations);
            }
            $return = false;
            logfile('-- Tạo thành công design');
        } else {
            logfile('-- Không có order để tạo design');
            $return = true;
        }
        return $return;
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
                                $extension = strtolower(pathinfo($image->src)['extension']);
                                $rand = strRandom();
                                $img .= env('URL_LOCAL').genThumb($woo_id.$product_id.'_'.$rand.'.'.$extension, $image->src, env('THUMB')) . ",";
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
        $str_sku = preg_replace('/\s+/', '', ucwords(strtolower($str_sku)));
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
                $r = $this->makeFileTemplate($i, $id_store, $template_id);
                $result = $r['result'];
                $template_path = $r['template_path'];
                $template_name = $r['template_name'];
                $variation_list = $r['variation_list'];
                $path = $r['path'];
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

            // lấy tên và id của category
            if (isset($template_data['categories'][0]))
            {
                $tem_category = $template_data['categories'][0];
                $category_name = $tem_category['name'];
                $woo_category_id = $tem_category['id'];
            } else {
                $category_name = null;
                $woo_category_id = null;
            }

            // kiểm tra với woo_categories có sẵn tại tool xem tồn tại chưa.
            $check_category = \DB::table('woo_categories')->select('id')
                ->where([
                    ['name', '=', $category_name],
                    ['store_id', '=', $id_store]
                ])->first();
            if ($check_category != NULL)
            {
                $category_id = $check_category->id;
            } else {
                $woocommerce = $this->getConnectStore($rq['url'], $rq['consumer_key'], $rq['consumer_secret']);
                $data = [
                    'slug' => $category_name,
                ];
                // kết nối tới woocommerce store để lấy thông tin
                $result = ($woocommerce->get('products/categories', $data));
                $category_id = $result[0]->id;
                $data = [
                    'woo_category_id' => $woo_category_id,
                    'name' => $category_name,
                    'slug' => $result[0]->slug,
                    'store_id' => $id_store,
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                \DB::table('woo_categories')->insert($data);
            }
            $category_data = [
                'category_id' => $category_id,
                'category_name' => $category_name,
                'woo_category_id' => $woo_category_id
            ];
            $data = array();
            if ($scrap != null) {
                return redirect('scrap-create-template')->with('success', 'Connect với template thành công');
            } else {
                return view("/admin/woo/save_path_template", compact('data', "template_data", 'rq', 'category_data'));
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function autoUploadProduct()
    {
        $return = false;
        $result_check_tag = $this->checkTag();
        if ($result_check_tag) {
            $return = $this->checkCreateProduct();
        }
        return $return;
    }

    // kiểm tra tag để lưu vào product
    private function checkTag()
    {
        logfile('--[ Check Tag ] ---------------------------');
        $lst_product_tag = \DB::table('woo_product_drivers as spd')
            ->join('woo_infos as woo_info', 'spd.store_id', '=', 'woo_info.id')
            ->select(
                'spd.id as scrap_product_id', 'spd.tag_name', 'spd.store_id',
                'woo_info.url', 'woo_info.consumer_key', 'woo_info.consumer_secret'
            )
            ->where([
                ['woo_tag_id', '=', NULL],
                ['tag_name', '<>', NULL]
            ])
            ->limit(30)
            ->get()->toArray();
        if (sizeof($lst_product_tag) > 0) {
            $tag_store_lst = array();
            $tmp = array();
            $scrap_product_update = array();
            // cập nhật tag_id vào woo_product_drivers
            $tags = \DB::table('woo_tags')
                ->select('id', 'store_id', 'slug')
                ->get()->toArray();
            // tạo mảng mới có key là store_id và name folder để so sánh
            $compare_tag = array();
            foreach ($tags as $tag) {
                $key = $tag->store_id . '_' . $tag->slug;
                $compare_tag[$key] = $tag->id;
            }
            foreach ($lst_product_tag as $val) {
                $val->tag_name = strtolower($val->tag_name);
                $key_compare = $val->store_id . '_' . $val->tag_name;
                //nếu đã tồn tại
                if (array_key_exists($key_compare, $compare_tag)) {
                    $scrap_product_update[$compare_tag[$key_compare]][] = $val->scrap_product_id;
                } else { // nếu chưa tồn tại. lưu vào 1 mảng khác để truy vấn.
                    if (!in_array($val->tag_name, $tmp)) {
                        $tmp[] = $val->tag_name;
                    }
                    $tag_store_lst[$val->store_id] = [
                        'url' => $val->url,
                        'consumer_key' => $val->consumer_key,
                        'consumer_secret' => $val->consumer_secret,
                        'tags' => $tmp
                    ];
                }
            }
            //nếu tồn tại sản phẩm chưa có tag
            if (sizeof($tag_store_lst) > 0) {
                $woo_tags_data = array();
                foreach ($tag_store_lst as $store_id => $info) {
                    $woocommerce = $this->getConnectStore($info['url'], $info['consumer_key'], $info['consumer_secret']);
                    foreach ($info['tags'] as $tag_name) {
                        $data = [
                            'slug' => $tag_name,
                        ];
                        // kết nối tới woocommerce store để lấy thông tin
                        $result = ($woocommerce->get('products/tags', $data));
                        //nếu không thấy thông tin thì tạo mới
                        if (sizeof($result) == 0) {
                            $data = [
                                'name' => $tag_name
                            ];
                            $i = ($woocommerce->post('products/tags', $data));
                            $woo_tags_data[] = [
                                'woo_tag_id' => $i->id,
                                'name' => $i->name,
                                'slug' => $i->slug,
                                'store_id' => $store_id,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        } else {
                            $woo_tags_data[] = [
                                'woo_tag_id' => $result[0]->id,
                                'name' => $result[0]->name,
                                'slug' => $result[0]->slug,
                                'store_id' => $store_id,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        }
                    }
                }
                //them toan bo thong tin woo_tags mới get được về database
                if (sizeof($woo_tags_data) > 0) {
                    logfile('-- Tạo mới thông tin woo_tags : ' . sizeof($woo_tags_data) . ' news');
                    \DB::table('woo_tags')->insert($woo_tags_data);
                }
            }

            // Nếu tồn tại thông tin để update vào sản phẩm scrap_products
            if (sizeof($scrap_product_update) > 0) {
                logfile('-- Cập nhật thông tin tag vào woo_product_drivers : ' . sizeof($scrap_product_update) . ' update.');
                foreach ($scrap_product_update as $woo_tag_id => $list_id) {
                    $data = [
                        'woo_tag_id' => $woo_tag_id
                    ];
                    \DB::table('woo_product_drivers')->whereIn('id', $list_id)->update($data);
                }
            }
            $result = false;
        } else {
            $result = true;
            logfile('-- Đã chuẩn bị đủ tag. Chuyển sang tạo mới sản phẩm.');
        }
        return $result;
    }

    public function autoUploadImage()
    {
        $return = false;
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
                    logfile_system("-- Đang tải " . sizeof($checks) . " images từ store :" . $val->url);
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
                                'date_created' => date("Y-m-d H:i:s", strtotime(" -2 days"))
                            );
                            $update_images_data['update'][] = $tmp;
                            $result = $woocommerce->put('products/' . $product_id, $tmp);
                            if ($result) {
                                $myarray = (array)$result;
                                $slug_data[$store_id][$product_id] = $myarray['permalink'];
                                $product_update_data[] = $product_id;
                                $product_image_uploaded = array_merge($product_image_uploaded, $store['woo_up_id'][$product_id]);
                                logfile_system('--- Upload thành công: ' . sizeof($images) . ' của product_id: ' . $product_id);
                            } else {
                                $product_update_data_false[] = $product_id;
                                logfile_system('--- [Error] Upload thất bại: ' . sizeof($images) . ' của product_id: ' . $product_id);
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
                    logfile_system('-- [END] Hoàn tất tiến trình upload ảnh.');
                } else {
                    logfile_system('-- [END] Đã hết ảnh từ google driver để tải lên woocommerce. Kết thúc.');
                    logfile_system('-- Chuyển sang xóa sản phẩm');
                    $return = $this->deleteProductUploaded();
                }
            } else {
                $result = true;
                if ($result) {
                    logfile_system('-- Chuyển sang xóa sản phẩm');
                   $return = $this->deleteProductUploaded();
                }
            }
        } catch (\Exception $e) {
            logfile_system($e->getMessage());
            return $e->getMessage();
        }
        return $return;
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
                ->limit(50)
                ->get()->toArray();
            logfile_system('-- Đang xóa ' . sizeof($lists) . ' categories');
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
                logfile_system($str);
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
        $return = false;
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
                    logfile_system('-- [Success] Đã xóa ' . sizeof($delete) . ' sản phẩm thành công.');
                } else {
                    logfile_system('-- [Error] Không thể xóa sản phẩm thuộc template= ' . $temps->template_id . ' và store: ' . $temps->store_id);
                }
            } else {
                \DB::table('woo_templates')->where('id', $temps->id)->update(['status' => 24]);
                logfile_system('-- Đã hết sản phẩm để xóa.');
            }
        } else {
            $return = true;
            logfile_system('-- [End] Đã hết template để xóa.');
        }
        return $return;
    }

    /*Tao moi product*/
    private function checkCreateProduct()
    {
        $return = false;
        try {
            logfile_system('=== [Create Product] ========================================');
            //kiểm tra xem có file nào đang up dở hay không
            $check_processing = \DB::table('woo_product_drivers')->select('name', 'template_id')->where('status', 2)->first();
            //nếu không có file nào đang up dở
            if ($check_processing == NULL) {
                $limit = 10;
                $check = \DB::table('woo_product_drivers as wopd')
                    ->join('woo_infos as woo_info', 'wopd.store_id', '=', 'woo_info.id')
                    ->join('woo_tags','woo_tags.id', '=', 'wopd.woo_tag_id')
                    ->join('woo_templates as woo_temp', function ($join) {
                        $join->on('wopd.template_id', '=', 'woo_temp.template_id');
                        $join->on('wopd.store_id', '=', 'woo_temp.store_id');
                    })
                    ->select(
                        'wopd.id as woo_product_driver_id', 'wopd.name', 'wopd.path', 'wopd.template_id', 'wopd.store_id',
                        'wopd.woo_category_id',
                        'woo_tags.woo_tag_id', 'woo_tags.name as tag_name', 'woo_tags.slug as tag_slug',
                        'woo_temp.template_path',
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
                        logfile_system("-- Đang tạo sản phẩm mới : " . $woo_product_name);
                        $prod_data = $template_json;
                        $prod_data['name'] = ucwords($val->name) . ' ' . $template_json['name'];
                        $prod_data['status'] = 'draft';
                        $prod_data['categories'] = [
                            ['id' => $val->woo_category_id]
                        ];
                        //them tag vao san pham
                        if ($val->woo_tag_id != '') {
                            $prod_data['tags'][] = [
                                'id' => $val->woo_tag_id,
                                'name' => $val->tag_name,
                                'slug' => $val->tag_slug,
                            ];
                        }
                        $prod_data['description'] = html_entity_decode($template_json['description']);
                        unset($prod_data['variations']);
                        // End tìm template

                        // Tìm image
                        $scan_images = scanGoogleDir($val->path, 'file');
//                        dd($scan_images);
                        logfile_system('---- Tìm được ' . sizeof($scan_images) . ' ảnh của ' . ucwords($val->name));
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
                                    logfile_system('----- Chấp nhận file ' . $file['name']);
                                }
                            }
                        }
                        logfile_system('---- Tìm được ' . $m . ' ảnh của ' . ucwords($val->name));
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
                        logfile_system('-- Cập nhật variations vào product');
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
//                                    logfile_system('-- Đang cập nhật variation của '.$variation->woo_product_id);
                                }
                            }
                        }
                    }
                    if (sizeof($image_local) > 0) {
                        \DB::table('woo_image_uploads')->insert($image_local);
                    }
                    logfile_system('-- [END] Hoàn tất quá trình tạo sản phẩm.');
                } else {
                    $return = true;
                    logfile_system('-- [END] Đã hết product để chuẩn bị dữ liệu.');
                }
            } else {
                logfile_system('[Bỏ qua] Hiện đang tạo product : "' . $check_processing->name . '" có template_id :' . $check_processing->template_id);
            }
        } catch (\HttpClientException $e) {
            logfile_system($e->getMessage());
        }
        return $return;
    }

    // Google Feed
    public function getCategoryChecking()
    {
        $re = true;
        $check_running = \DB::table('check_categories')->select('id')->where('status', 1)->first();
        $count_category = \DB::table('check_categories')->select('id')->count();
        if ($check_running == NULL && $count_category > 0) {
            $category = \DB::table('check_categories')
                ->select('id', 'category_id', 'store_id')->where('status', 0)->orderBy('created_at', 'ASC')->first();
            $category = json_decode(json_encode($category ,true),true);
            if ($category != NULL && sizeof($category) > 0) {
                $check_categories_id = $category['id'];
                // cap nhat trang thai check categories
                \DB::table('check_categories')->where('id',$check_categories_id)->update(['status' => 1]);
                $category_id = $category['category_id'];
                $store_id = $category['store_id'];
                $select = ['id', 'category_name', 'tag_name', 'store_id', 'woo_product_id', 'woo_product_name', 'woo_slug'];
                //lay danh sach feed chưa xong ra để xóa những product chưa cập nhật xong
                $lst_feeds = \DB::table('feed_products')->select($select)
                    ->where('category_id', $category_id)
                    ->where('store_id', $store_id)
                    ->get()->toArray();
                // lay toan bo product scrap ra de so sanh loai tru
                $lst_products = \DB::table('scrap_products')
                    ->select($select)
                    ->where('woo_category_id', $category_id)
                    ->where('store_id', $store_id)
                    ->get()->toArray();
                $ar_feeds = array();
                $lst_delete = array();
                foreach ($lst_feeds as $f) {
                    $ar_feeds[$f->woo_product_id] = [
                        'category_id' => $category_id,
                        'category_name' => $f->category_name,
                        'tag_name' => $f->tag_name,
                        'store_id' => $f->store_id,
                        'woo_product_id' => $f->woo_product_id,
                        'woo_product_name' => $f->woo_product_name,
                        'woo_slug' => $f->woo_slug
                    ];
                    $lst_delete[$f->woo_product_id] = $f->id;
                }
                // bắt đầu so sánh feed và scrap product
                $delete_feed_product = array();
                $insert_feed_data = array();
                $update_feed_check_again = array();
                foreach ($lst_products as $product) {
                    $tmp_data = [
                        'woo_product_name' => $product->woo_product_name,
                        'woo_slug' => $product->woo_slug,
                        'category_id' => $category_id,
                        'woo_product_id' => $product->woo_product_id,
                        'store_id' => $product->store_id,
                        'category_name' => $product->category_name,
                        'tag_name' => $product->tag_name,
                        'scrap_product_id' => $product->id,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                    // nếu đã tồn tại rồi thì kiểm tra dữ liệu có trùng hay không. Nếu không trùng xóa đi và thêm mới.
                    if (array_key_exists($product->woo_product_id, $ar_feeds)) {
                        $tmp = [
                            'category_id' => $category_id,
                            'category_name' => $product->category_name,
                            'tag_name' => $product->tag_name,
                            'store_id' => $product->store_id,
                            'woo_product_id' => $product->woo_product_id,
                            'woo_product_name' => $product->woo_product_name,
                            'woo_slug' => $product->woo_slug
                        ];
                        $result = array_diff($tmp, $ar_feeds[$product->woo_product_id]);
                        if (!empty($result)) {
                            $delete_feed_product[] = $lst_delete[$product->woo_product_id];
                            $insert_feed_data[] = $tmp_data;
                        } else {
                            $update_feed_check_again[] = $lst_delete[$product->woo_product_id];
                        }
                    } else {
                        $insert_feed_data[] = $tmp_data;
                    }
                }
                // nếu tồn tại 2 giá trị feed và scrap product khác nhau. xóa đi và insert lại
                if (sizeof($delete_feed_product) > 0) {
                    \DB::table('feed_products')->whereIn('id', $delete_feed_product)->delete();
                    logfile_system('-- Xóa sản phẩm trên feed thông tin đã cũ.');
                }

                // nếu tồn tại giá trị cần insert thì tạo mới 1 loạt sản phẩm trong feed theo info của scrap product.
                if (sizeof($insert_feed_data) > 0) {
                    \DB::table('feed_products')->insert($insert_feed_data);
                    logfile_system('-- Thêm dữ liệu đã được cập nhật từ scrap product');
                }

                // nếu giá trị được so sánh giống hoàn toàn với dữ liệu cũ. cập nhật lại để check
                if (sizeof($update_feed_check_again) > 0)
                {
                    \DB::table('feed_products')->whereIn('id',$update_feed_check_again)->update([
                        'status' => 0,
                        'check' => 0,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                }

                \DB::table('check_categories')->where('id',$check_categories_id)->update(['status' => 2]);
                $re = false;
            } else {
                logfile_system('-- Category đã được cập nhật thông tin mới nhất.');
            }
        } else {
            if ($check_running)
            {
                logfile_system('-- Đang cập nhật check category id : ' . $check_running->id);
            } else {
                logfile_system('-- Không có category để check');
            }
        }
        return $re;
    }

    public function reCheckProductInfo()
    {
        $re = true;
        logfile_system('-- Đang check feed product');
        $limit = 20;
        $products = \DB::table('feed_products')
            ->select('id', 'woo_product_name', 'woo_slug', 'woo_image', 'woo_product_id', 'category_name', 'store_id',
                'scrap_product_id','description')
            ->where('check', 0)->limit($limit)->get()->toArray();
        $stores = \DB::table('woo_infos')->select('id', 'url', 'consumer_key', 'consumer_secret')->get()->toArray();
        $categories = \DB::table('woo_categories')->select('id', 'woo_category_id', 'name', 'store_id')->get()->toArray();
        if (sizeof($products) > 0) {
            // lấy toàn bộ danh sách category để phân loại store sau đó so sánh categories
            $ar_categories = array();
            foreach ($categories as $category) {
                $ar_categories[$category->store_id][$category->name] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'woo_category_id' => $category->woo_category_id,
                ];
            }
            // lấy thông tin đăng nhập vào API của tất cả store
            $ar_stores = array();
            foreach ($stores as $store) {
                $ar_stores[$store->id] = json_decode(json_encode($store, true), true);
            }
            $check_data = array();
            foreach ($products as $product) {
                if (array_key_exists($product->store_id, $ar_stores)) {
                    $check_data[$product->store_id]['woo'] = $ar_stores[$product->store_id];
                    $check_data[$product->store_id]['feed'][] = json_decode(json_encode($product, true), true);
                }
            }
            $lst_feed_id = array();
            $data_update_feed = array();
            $lst_scrap_id = array();
            $data_update_scrap = array();
            // ket noi toi store woo de kiem tra thong tin san pham
            foreach ($check_data as $store_id => $v) {
                $woo_info = $v['woo'];
                $woocommerce = $this->getConnectStore($woo_info['url'], $woo_info['consumer_key'], $woo_info['consumer_secret']);
                foreach ($v['feed'] as $feed) {
//                print_r($feed);
                    // tạo ra array của sản phẩm trong database
                    $array_old = [
                        'woo_product_name' => trim($feed['woo_product_name']),
                        'woo_slug' => trim($feed['woo_slug']),
                        'woo_image' => trim($feed['woo_image']),
                        'woo_product_id' => trim($feed['woo_product_id']),
                        'category_name' => trim($feed['category_name'])
                    ];
//                print_r($array_old);
                    $result = ($woocommerce->get('products/' . $feed['woo_product_id']));
                    // tạo ra array của info sản phẩm cần so sánh
                    $array_new = [
                        'woo_product_name' => trim($result->name),
                        'woo_slug' => trim($result->permalink),
                        'woo_image' => trim($result->images[0]->src),
                        'woo_product_id' => trim($result->id),
                        'category_name' => trim($result->categories[0]->name)
                    ];
//                print_r($result);
                    $result_diff = array_diff($array_new, $array_old);
                    // neu 2 array khac nhau
                    if (sizeof($result_diff) > 0) {
                        $data_update_feed[$feed['id']] = [
                            'woo_product_name' => $result->name,
                            'woo_slug' => $result->permalink,
                            'description' => strip_tags($result->description),
                            'woo_image' => $result->images[0]->src,
                            'woo_product_id' => $result->id,
                            'category_name' => $result->categories[0]->name,
                            'status' => 1,
                            'check' => 1,
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $data_update_scrap[$feed['scrap_product_id']] = [
                            'woo_product_name' => $result->name,
                            'woo_slug' => $result->permalink,
                            'woo_product_id' => $result->id,
                            'category_name' => $result->categories[0]->name,
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        logfile_system(' --- Đang check feed id: ' . $feed['id'] . ' : Thong tin khac nhau');
                    } else {
                        logfile_system(' --- Đang check feed id: ' . $feed['id'] . ' : Thong tin giong nhau');
                        // kiểm tra xem nếu description rỗng thì thêm mới
                        if ($feed['description'] == '')
                        {
                            $data_update_feed[$feed['id']] = [
                                'description' => strip_tags($result->description),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        } else {
                            $lst_feed_id[] = $feed['id'];
                        }
                    }
                }
            }

            if (sizeof($lst_feed_id) > 0) {
                \DB::table('feed_products')->whereIn('id', $lst_feed_id)->update([
                    'status' => 1,
                    'check' => 1,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            }
            if (sizeof($data_update_feed) > 0) {
                foreach ($data_update_feed as $feed_id => $data) {
                    \DB::table('feed_products')->where('id', $feed_id)->update($data);
                }
            }

            if (sizeof($data_update_scrap) > 0) {
                foreach ($data_update_scrap as $scrap_id => $data) {
                    \DB::table('scrap_products')->where('id', $scrap_id)->update($data);
                }
            }
            $re = false;
        } else {
            logfile_system('-- [DONE] Đã check xong toàn bộ feed product');
        }
        return $re;
    }

    public function getMoreWooCategory($request)
    {
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $store_id = $rq['store_id'];
            $category_name = $rq['category_name'];
            $alert = 'error';
            $check = \DB::table('woo_categories')
                ->where('slug', $category_name)
                ->where('store_id', $store_id)
                ->first();
            if ($check == NULL) {
                $woo_info = \DB::table('woo_infos')->select('*')->where('id', $store_id)->first();
                $woocommerce = $this->getConnectStore($woo_info->url, $woo_info->consumer_key, $woo_info->consumer_secret);
                $data = [
                    'slug' => $category_name,
                ];
                // kết nối tới woocommerce store để lấy thông tin
                $result = ($woocommerce->get('products/categories', $data));
                if (sizeof($result) > 0) {
                    $insert_data = [
                        'woo_category_id' => $result[0]->id,
                        'name' => $result[0]->name,
                        'slug' => $result[0]->slug,
                        'store_id' => $store_id,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                    $result_insert = \DB::table('woo_categories')->insert($insert_data);
                    if ($result_insert) {
                        $alert = 'success';
                        $message = 'Thêm category ' . $result[0]->name . ' thành công';
                    } else {
                        $message = 'Xảy ra lỗi. Không thể thêm category ' . $result[0]->name . '. Mời bạn thử lại.';
                    }
                } else {
                    $message = 'Không tồn tại category ' . $category_name . ' này tại store ' . $woo_info->name;
                }
            } else {
                $message = 'Đã tồn tại category "' . $category_name . '" này ở hệ thống. Mời bạn kiểm tra lại ở phần trên';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            logfile($e->getMessage());
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return redirect('list-categories')->with($alert, $message);
    }
    // End Google feed

    public function editWooTemplate($request)
    {
        $message_status = 'error';
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $message = '';
            $status = 1;
            if ($rq['product_code'] != '' || $rq['sale_price'] != '' || $rq['origin_price'] != '' || $rq['product_name_exclude'] != '' || $rq['product_name_change'] != '') {
                $status = 2;
            }
            $product_name = ucwords(trim($rq['product_name']));
            $product_code = ($rq['product_code'] != '') ? trim($rq['product_code']) : NULL;
            $sale_price = ($rq['product_code'] != '') ? trim($rq['sale_price']) : 0;
            $origin_price = ($rq['product_code'] != '') ? trim($rq['origin_price']) : 0;
            $product_name_exclude = ($rq['product_code'] != '') ? ucwords(trim($rq['product_name_exclude'])) : NULL;
            $product_name_change = ($rq['product_code'] != '') ? ucwords(trim($rq['product_name_change'])) : NULL;
            $id = trim($rq['id']);
            $woo_info = \DB::table('woo_templates')
                ->join('woo_infos', 'woo_templates.store_id', '=', 'woo_infos.id')
                ->select(
                    'woo_templates.template_id', 'woo_templates.template_path',
                    'woo_infos.id as store_id', 'woo_infos.url', 'woo_infos.consumer_key', 'woo_infos.consumer_secret'
                )
                ->where('woo_templates.id', $id)
                ->first();
            try {
                $woocommerce = $this->getConnectStore($woo_info->url, $woo_info->consumer_key, $woo_info->consumer_secret);
                $template_old = readFileJson($woo_info->template_path);
                $update = [
                    'name' => $product_name,
                    'price' => ($sale_price > 0) ? $sale_price : $template_old['price'],
                    'regular_price' => ($origin_price > 0) ? $origin_price : $template_old['regular_price'],
                    'sale_price' => ($sale_price > 0) ? $sale_price : $template_old['sale_price']
                ];
                $update_template = $woocommerce->put('products/' . $woo_info->template_id, $update);
                $try = true;
            } catch (\Exception $e) {
                $try = false;
            }
            if ($try) {
                $r = $this->makeFileTemplate($update_template, $woo_info->store_id, $woo_info->template_id);
                $result = $r['result'];
                $template_path = $r['template_path'];
                $update_db = [
                    'product_name' => $product_name,
                    'product_code' => $product_code,
                    'sale_price' => $sale_price,
                    'origin_price' => $origin_price,
                    'product_name_exclude' => $product_name_exclude,
                    'product_name_change' => $product_name_change,
                    'template_path' => $template_path,
                    'status' => $status,
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                if ($result) {
                    $result_update = \DB::table('woo_templates')->where('id', $id)->update($update_db);
                    \DB::table('scrap_products')
                        ->where('template_id',$woo_info->template_id)
                        ->whereNotNull('woo_product_id')
                        ->update(['status_tool' => 1]);
                    if ($result_update) {
                        $message_status = 'success';
                        $message = 'Cập nhật template thành công.';
                    } else {
                        $message = 'Cập nhật template thất bại. Mời bạn thử lại';
                    }
                }
            } else {
                $message = 'Không thể kết nối tới store. Mời bạn thử lại.';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            $message = 'Xảy ra lỗi nội bộ : ' . $e->getMessage();
        }
        return redirect('woo-get-template')->with($message_status, $message);
    }

    private function makeFileTemplate($templates, $id_store, $template_id)
    {
        $template_data = json_decode(json_encode($templates), True);
        $template_name = $template_data['name'];
        $description = htmlentities(str_replace("\n", "<br />", $template_data['description']));
        $template_data['description'] = $description;
        //xoa cac key khong can thiet
        $deleted = array('id', 'slug', 'permalink', 'price_html', 'images', '_links');
        $variation_list = $template_data['variations'];
        foreach ($deleted as $v) {
            unset($template_data[$v]);
        }
        //tao thu muc de luu template
        $path = storage_path('app/public') . '/template/' . $id_store . '/' . $template_id . '/';
        makeFolder(($path));
        $count_file = sizeof(array_diff(scandir($path), array('.', '..'))) + 1;
        // Write File
        $template_path = $path . 'temp_' . $template_id .'_'.$count_file. '.json';
        $template_data['meta_data'] = [];
        $result = writeFileJson($template_path, $template_data);
        chmod($template_path, 777);
        $return = [
            'result' => $result,
            'template_path' => $template_path,
            'template_name' => $template_name,
            'variation_list' => $variation_list,
            'path' => $path
        ];
        return $return;
    }

    public function changeInfoProduct()
    {
        $return = false;
        \DB::beginTransaction();
        try {
            logfile_system('==== Bắt đầu thay đổi thông tin product =========================');
            $products = \DB::table('scrap_products as scp')
                ->leftjoin('woo_templates as wtp', function ($join) {
                    $join->on('scp.template_id', '=', 'wtp.template_id')
                        ->on('scp.store_id', '=', 'wtp.store_id');
                })
                ->select(
                    'scp.id as scrap_product_id', 'scp.template_id', 'scp.woo_product_id', 'scp.store_id',
                    'scp.woo_product_name', 'scp.woo_slug',
                    'wtp.product_name as temp_product_name', 'wtp.product_code', 'wtp.product_name_change',
                    'wtp.product_name_exclude', 'wtp.template_path'
                )
                ->where('scp.status_tool', 1)
                ->where('scp.status', 1)
                ->limit(19)
                ->get()->toArray();
            if (sizeof($products) > 0) {
                // lấy thông tin API ở store ra
                $infos = \DB::table('woo_infos')->select('id', 'url', 'consumer_key', 'consumer_secret')->get()->toArray();
                // gộp thông tin api theo id của store
                $woo_infos = array();
                foreach ($infos as $info) {
                    $woo_infos[$info->id] = json_decode(json_encode($info, true), true);
                }
                // dồn thông tin api vào các product
                $tmp = array();
                foreach ($products as $item) {
                    if (array_key_exists($item->store_id, $woo_infos)) {
                        $tmp[$item->store_id]['info'] = $woo_infos[$item->store_id];
                        $tmp[$item->store_id]['data'][] = json_decode(json_encode($item, true), true);
                    }
                }
                $scrap_success = array();
                $scrap_id_success = array();
                $scrap_id_error = array();
                foreach ($tmp as $store_id => $value) {
                    $info = $value['info'];
                    $data = $value['data'];
                    $woocommerce = $this->getConnectStore($info['url'], $info['consumer_key'], $info['consumer_secret']);
                    foreach ($data as $item) {
                        $info_template = readFileJson($item['template_path']);
                        $product_name = str_replace($item['product_code'], '', $item['woo_product_name']);
                        $product_name = str_replace(ucwords($item['product_name_exclude']), ucwords($item['product_name_change']), $product_name);
                        $product_name = trim(trim($product_name) . " " . trim($item['product_code']));

                        $update = [
                            'name' => $product_name,
                            'permalink' => $item['woo_slug'],
                            'price' => $info_template['price'],
                            'regular_price' => $info_template['regular_price'],
                            'sale_price' => $info_template['sale_price']
                        ];
                        try {
                            $result_change = $woocommerce->put('products/' . $item['woo_product_id'], $update);
                            $check = true;
                        } catch (\Exception $e) {
                            $check = false;
                            logfile_system('-- Không connect được với product id : ' . $item['woo_product_id']);
                        }
                        if ($check) {
                            $scrap_success[$item['scrap_product_id']] = [
                                'woo_product_name' => $product_name,
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            $scrap_id_success[] = $item['scrap_product_id'];
                            logfile_system('-- Thay đổi thông tin scrap id: ' . $item['scrap_product_id'] . ' thành công');
                        } else {
                            $scrap_id_error[] = $item['scrap_product_id'];
                            logfile_system('-- [E] Thay đổi thông tin scrap id: ' . $item['scrap_product_id'] . ' thất bại');
                        }
                    }

                }
                // nếu thay đổi thông tin product thành công. Thì cập nhật scrap_product và feed_product
                if (sizeof($scrap_id_success) > 0) {
                    $check_feed_products = \DB::table('feed_products')->whereIn('scrap_product_id', $scrap_id_success)
                        ->pluck('id', 'scrap_product_id')->toArray();
                    \DB::table('scrap_products')->whereIn('id', $scrap_id_success)->update(['status_tool' => 0]);
                    foreach ($scrap_success as $scrap_product_id => $update) {
                        \DB::table('scrap_products')->where('id', $scrap_product_id)->update($update);
                        if (array_key_exists($scrap_product_id, $check_feed_products)) {
                            \DB::table('scrap_products')->where('id', $scrap_product_id)->update($update);
                        }
                    }
                }

                // nếu không thể cập nhật được thông tin product. Thì product đó đã xóa trước rồi. Xóa ở scrap_product và feed product
                if (sizeof($scrap_id_error) > 0) {
                    $check_feed_products = \DB::table('feed_products')->whereIn('scrap_product_id', $scrap_id_error)
                        ->pluck('id')->toArray();
                    \DB::table('scrap_products')->whereIn('id', $scrap_id_error)->delete();
                    if (sizeof($check_feed_products) > 0) {
                        \DB::table('scrap_products')->whereIn('id', $scrap_id_error)->delete();
                    }
                }
            } else {
                logfile_system('-- Đã hết product thay đổi thông tin. Chuyển sang công việc khác.');
                $return = true;
                \DB::table('woo_templates')->where('status', 2)->update([
                    'status' => 1,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            $message = 'Xảy ra lỗi nội bộ : ' . $e->getMessage();
        }
        return $return;
    }

    /*Hafm tam thoi. sau nay se xoa*/
    public function getAllOrderOld()
    {
        $full_order = array(
            35005, 36030, 40554, 41177, 41287, 41471, 41498, 46122, 47300, 47566, 48062, 48680, 48682, 48725, 48800, 49760, 49985, 49997, 50011, 50382, 50622, 50868, 51419, 51860, 52117, 52186, 52658, 52702, 52840, 52943, 52976, 53004, 53255, 53354, 53876, 53888, 54090, 56948, 17298, 34674, 34703, 35939, 35956, 36022, 36056, 37635, 40429, 40612, 40634, 40668, 40670, 40684, 40692, 41022, 41044, 41243, 41253, 41333, 41427, 41512, 41718, 41851, 41860, 41868, 41940, 42014, 44794, 44814, 44822, 44882, 44958, 45168, 45189, 46149, 46170, 46227, 46248, 46305, 46812, 46902, 46911, 46929, 46989, 47028, 47040, 47136, 47142, 47258, 47362, 47384, 47402, 47410, 47440, 47442, 47446, 47456, 47475, 47515, 47523, 47539, 47572, 47580, 47602, 47612, 47640, 48004, 48006, 48018, 48025, 48032, 48051, 48054, 48122, 48142, 48146, 48238, 48250, 48274, 48412, 48414, 48464, 48576, 48698, 48723, 48948, 48957, 48961, 49369, 49604, 49606, 49648, 49673, 49688, 49693, 49696, 49697, 49702, 49712, 49734, 49746, 49771, 49774, 49768, 49961, 49963, 49737, 50002, 50003, 50008, 50016, 50025, 50055, 50056, 50070, 50078, 50096, 50140, 50147, 50178, 50179, 50203, 50253, 50283, 50289, 50292, 50344, 50373, 50374, 50376, 50424, 50426, 50434, 50455, 50634, 50646, 50659, 50724, 50749, 50808, 50822, 51052, 51058, 51162, 51172, 51219, 51350, 51452, 51614, 51696, 51711, 51783, 51804, 51897, 52064, 52068, 52100, 52228, 52276, 52315, 52353, 52476, 52607, 52696, 52720, 52727, 52729, 52731, 52791, 52861, 52902, 52993, 53040, 53059, 53061, 53063, 53078, 53119, 53143, 53213, 53323, 53347, 53356, 53364, 53806, 53817, 53854, 53860, 53942, 53977, 53983, 53987, 54042, 54053, 54136, 54882, 55433, 55664, 55666, 55674, 55683, 55715, 55723, 55729, 55751, 55767, 55769, 55816, 55869, 56539, 56547, 56612, 56662, 56696, 56815, 56832, 56860, 57128, 57147, 57180, 57193, 57205, 57243, 17505, 34739, 34937, 34990, 42008, 44821, 45051, 47121, 47458, 47288, 47561, 47992, 47571, 48071, 47997, 48550, 48556, 48973, 50080, 50194, 50104, 50307, 50356, 50990, 52414, 52586, 54047, 56777, 57021, 56997, 17359, 17383, 17439, 17444, 17482, 17489, 17503, 17508, 22008, 26923, 34667, 34669, 34671, 34678, 34680, 34682, 34684, 34686, 34688, 34690, 34692, 34694, 34696, 34698, 34700, 34705, 34707, 34709, 34711, 34713, 34715, 34717, 34719, 34721, 34723, 34727, 34729, 34731, 34733, 34725, 34735, 34737, 34741, 34743, 34745, 34747, 34749, 34751, 34754, 34756, 34758, 34760, 34762, 34764, 34766, 34768, 34770, 34772, 34774, 34776, 34778, 34780, 34782, 34926, 34928, 34930, 34933, 34935, 34939, 34941, 34943, 34945, 34947, 34949, 34951, 34953, 34955, 34957, 34959, 34961, 34963, 34965, 34967, 34969, 34971, 34978, 34985, 34988, 34993, 34997, 34999, 34995, 35003, 35007, 35009, 35011, 35013, 35015, 35017, 35931, 35933, 35935, 35937, 35941, 35944, 35946, 35948, 35950, 35952, 35954, 35958, 35960, 35962, 35964, 35966, 35968, 35970, 35972, 35974, 35976, 35978, 35980, 35982, 35984, 35986, 35988, 35990, 35992, 35994, 35996, 35998, 36000, 36002, 36004, 36006, 36008, 36010, 36012, 36014, 36016, 36018, 36020, 36024, 36026, 36028, 36032, 36034, 36036, 36038, 36040, 36042, 36046, 36048, 36050, 36052, 36054, 36058, 36060, 36062, 36064, 36081, 36066, 36068, 36070, 36072, 36074, 36044, 36076, 36078, 36823, 36945, 37162, 37347, 37732, 38004, 38041, 38124, 38468, 38610, 38677, 38834, 38856, 38873, 38905, 39182, 39224, 39361, 39388, 39465, 39477, 39499, 39546, 39558, 39595, 39622, 39659, 39713, 40060, 40263, 38211, 40442, 40531, 40536, 40538, 40540, 40542, 40546, 40548, 40550, 40552, 40556, 40558, 40560, 40562, 39778, 40564, 40566, 40568, 40570, 40572, 40574, 40576, 40544, 40578, 40580, 40582, 40584, 40586, 40588, 40590, 40592, 40594, 40598, 40600, 40602, 40604, 40606, 40608, 40614, 40616, 40618, 40620, 40622, 40624, 40626, 40628, 40630, 40632, 40636, 40638, 40640, 40642, 40644, 40646, 40596, 40648, 40650, 40652, 40610, 40654, 40656, 40658, 40660, 40662, 40664, 40666, 40672, 40674, 40676, 40678, 40680, 40682, 40686, 40688, 40690, 40694, 40696, 40698, 40700, 40702, 40704, 40706, 40708, 40710, 40712, 40714, 40724, 40727, 40802, 40836, 40916, 41001, 41003, 41018, 41020, 41024, 41026, 41028, 41030, 41032, 41034, 41036, 41038, 41040, 41042, 41050, 41052, 41054, 41056, 41058, 41060, 41062, 41064, 41066, 41068, 41070, 41072, 41074, 41076, 41080, 41082, 41084, 41086, 41088, 41090, 41092, 41094, 41096, 41098, 41100, 41102, 41104, 41106, 41108, 41110, 41112, 41114, 41116, 41118, 41120, 41046, 41048, 41122, 41124, 41126, 41128, 41130, 41132, 41134, 41136, 41138, 41140, 41142, 41144, 41146, 41148, 41150, 41078, 41152, 41154, 41156, 41158, 41160, 41162, 41165, 41167, 41169, 41171, 41173, 41175, 41179, 41181, 41183, 41185, 41187, 41189, 41191, 41193, 41195, 41197, 41199, 41201, 41203, 41205, 41207, 41209, 41211, 41213, 41215, 41217, 41219, 41221, 41223, 41225, 41227, 41229, 41231, 41233, 41235, 41237, 41239, 41245, 41247, 41249, 41251, 41255, 41257, 41259, 41261, 41263, 41265, 41267, 41269, 41271, 41273, 41275, 41277, 41279, 41281, 41283, 41289, 41291, 41293, 41295, 41297, 41299, 41301, 41303, 41305, 41307, 41309, 41311, 41313, 41315, 41317, 41319, 41321, 41323, 41327, 41285, 41329, 41331, 41335, 41337, 41325, 41339, 41341, 41343, 41345, 41347, 41349, 41351, 41353, 41355, 41357, 41359, 41361, 41363, 41367, 41369, 41371, 41373, 41241, 41375, 41377, 41379, 41381, 41383, 41385, 41387, 41389, 41391, 41393, 41395, 41397, 41399, 41401, 41403, 41405, 41407, 41409, 41411, 41413, 41415, 41417, 41419, 41421, 41423, 41425, 41429, 41431, 41433, 41435, 41439, 41443, 41445, 41447, 41449, 41451, 41453, 41365, 41455, 41457, 41459, 41461, 41463, 41465, 41467, 41469, 41473, 41475, 41477, 41479, 41481, 41483, 41485, 41487, 41489, 41491, 41496, 41500, 41437, 41502, 41504, 41506, 41508, 41510, 41514, 41516, 41494, 41441, 41845, 41847, 41849, 41853, 41856, 41858, 41862, 41864, 41866, 41870, 41872, 41874, 41876, 41878, 41880, 41882, 41886, 41888, 41890, 41892, 41894, 41896, 41898, 41900, 41902, 41904, 41906, 41908, 41910, 41912, 41914, 41916, 41918, 41920, 41922, 41924, 41926, 41928, 41930, 41932, 41934, 41936, 41938, 41942, 41944, 41946, 41948, 41950, 41952, 41954, 41956, 41958, 41960, 41962, 41964, 41966, 41968, 41970, 41972, 41974, 41976, 41978, 41980, 41982, 41984, 41986, 41988, 41990, 41884, 41992, 41994, 41996, 42000, 42002, 42004, 42006, 42010, 42012, 42016, 42018, 42020, 42022, 42024, 42026, 42138, 42160, 42362, 42739, 42916, 42943, 43100, 41998, 43314, 43495, 43663, 44035, 44099, 44389, 44421, 44728, 44759, 44790, 44791, 44792, 44793, 44795, 44796, 44797, 44798, 44799, 44800, 44801, 44802, 44803, 44804, 44805, 44806, 44807, 44808, 44809, 44810, 44811, 44812, 44813, 44815, 44816, 44582, 44817, 44818, 44819, 44823, 44826, 44827, 44828, 44829, 44830, 44832, 44833, 44834, 44835, 44836, 44837, 44838, 44839, 44840, 44841, 44842, 44843, 44844, 44845, 44846, 44847, 44852, 44853, 44854, 44856, 44857, 44858, 44859, 44820, 44860, 44861, 44862, 44824, 44825, 44863, 44864, 44865, 44866, 44868, 44870, 44855, 44926, 44928, 44931, 44934, 44937, 44940, 44943, 44946, 44949, 44952, 44955, 44961, 44964, 44967, 44973, 44976, 44979, 44982, 44985, 44988, 44991, 44994, 44997, 45000, 45003, 45006, 45009, 45012, 45018, 45021, 45024, 45027, 45030, 45033, 45039, 45042, 45045, 45048, 45054, 45057, 45060, 45063, 45066, 45069, 45072, 45075, 44831, 45078, 45081, 45084, 45087, 45090, 45093, 45096, 45102, 45105, 45108, 45015, 45111, 45114, 45117, 45120, 45123, 45126, 45129, 45132, 45135, 45138, 45141, 45144, 45147, 45150, 45153, 45156, 45162, 45165, 45171, 45174, 45177, 45180, 45036, 45183, 45186, 45192, 45195, 45198, 45201, 45204, 45207, 45210, 45213, 45216, 45219, 45222, 45225, 45228, 45231, 45234, 45099, 45237, 45240, 45159, 45528, 45731, 45825, 45901, 44970, 45904, 45907, 45910, 45948, 45951, 45954, 45957, 45963, 45966, 45969, 45972, 45975, 45978, 45981, 45984, 45990, 45996, 45999, 46002, 46005, 46008, 46011, 46014, 46017, 46020, 46023, 46026, 46029, 46032, 46035, 46038, 46044, 46047, 46050, 46053, 46056, 46059, 46065, 46068, 46071, 46074, 45960, 46077, 46080, 46083, 46086, 46089, 46092, 45987, 46095, 45993, 46098, 46101, 46104, 46107, 46110, 46113, 46116, 46119, 46125, 46128, 46131, 46134, 46137, 46140, 46143, 46146, 46041, 46152, 46155, 46158, 46161, 46164, 46167, 46173, 46176, 46179, 46062, 46182, 46185, 46188, 46191, 46194, 46197, 46200, 46203, 46206, 46209, 46212, 46215, 46218, 46221, 46224, 46230, 46233, 46236, 46239, 46242, 46245, 46251, 46254, 46257, 46260, 46263, 46266, 46269, 46272, 46275, 46278, 46281, 46284, 46287, 46290, 46293, 46296, 46299, 46302, 46366, 46614, 46687, 46745, 46773, 46776, 46779, 46782, 46785, 46788, 46794, 46797, 46800, 46803, 46806, 46809, 46815, 46818, 46821, 46824, 46827, 46830, 46833, 46836, 46839, 46842, 46845, 46848, 46851, 46854, 46857, 46860, 46863, 46866, 46869, 46872, 46875, 46878, 46881, 46884, 46308, 46887, 46890, 46893, 46896, 46899, 46905, 46908, 46914, 46917, 46920, 46923, 46926, 46932, 46935, 46938, 46941, 46944, 46947, 46950, 46953, 46956, 46959, 46962, 46965, 46968, 46791, 46971, 46974, 46977, 46980, 46983, 46986, 46992, 46995, 46998, 47001, 47004, 47007, 47010, 47013, 47016, 47019, 47025, 47031, 47034, 47037, 47043, 47046, 47049, 47052, 47055, 47058, 47061, 47064, 47067, 47070, 47073, 47076, 47079, 47082, 47085, 47088, 47091, 47094, 47097, 47100, 47103, 47106, 47109, 47112, 47115, 47118, 47124, 47127, 47130, 47133, 47139, 47145, 47148, 47151, 47157, 47160, 47163, 47166, 47169, 47172, 47175, 47178, 47181, 47184, 47187, 47190, 47193, 47196, 47199, 47202, 47205, 47208, 47211, 47214, 47022, 47217, 47220, 47223, 47226, 47232, 47238, 47241, 47244, 47247, 47255, 47261, 47267, 47273, 47276, 47279, 47282, 47154, 47285, 47291, 47294, 47297, 47303, 47229, 47306, 47309, 47314, 47316, 47318, 47264, 47320, 47322, 47324, 47326, 47328, 47330, 47332, 47334, 47336, 47338, 47342, 47344, 47346, 47348, 47350, 47352, 47354, 47356, 47358, 47360, 47364, 47366, 47368, 47372, 47374, 47378, 47380, 47382, 47386, 47390, 47394, 47396, 47400, 47404, 47406, 47408, 47412, 47414, 47416, 47418, 47420, 47422, 47424, 47426, 47430, 47432, 47434, 47436, 47438, 47444, 47448, 47450, 47452, 47454, 47340, 47460, 47462, 47464, 47466, 47468, 47470, 47472, 47474, 47477, 47478, 47370, 47480, 47481, 47482, 47483, 47484, 47485, 47486, 47398, 47488, 47489, 47490, 47491, 47492, 47493, 47494, 47495, 47496, 47235, 47497, 47498, 47499, 47500, 47501, 47503, 47504, 47505, 47506, 47507, 47508, 47509, 47510, 47511, 47270, 47513, 47516, 47517, 47518, 47519, 47520, 47521, 47522, 47524, 47525, 47526, 47527, 47528, 47529, 47530, 47531, 47532, 47533, 47479, 47534, 47535, 47536, 47537, 47312, 47538, 47512, 47514, 47540, 47541, 47542, 47543, 47544, 47545, 47546, 47376, 47547, 47548, 47549, 47550, 47388, 47551, 47392, 47552, 47553, 47554, 47555, 47556, 47557, 47558, 47559, 47560, 47562, 47563, 47564, 47565, 47428, 47567, 47568, 47569, 47573, 47574, 47575, 47576, 47577, 47578, 47579, 47581, 47582, 47583, 47473, 47584, 47585, 47586, 47587, 47476, 47588, 47589, 47590, 47591, 47592, 47593, 47594, 47595, 47596, 47487, 47597, 47599, 47600, 47603, 47604, 47502, 47605, 47607, 47608, 47610, 47613, 47614, 47615, 47616, 47570, 47617, 47618, 47619, 47620, 47621, 47622, 47623, 47624, 47625, 47626, 47627, 47628, 47629, 47630, 47631, 47632, 47633, 47634, 47635, 47636, 47637, 47638, 47639, 47641, 47642, 47643, 47644, 47645, 47646, 47647, 47648, 47649, 47650, 47651, 47598, 47601, 47653, 47654, 47655, 47656, 47657, 47658, 47609, 47659, 47660, 47661, 47662, 47663, 47664, 47665, 47684, 47880, 47888, 47957, 47984, 47985, 47986, 47987, 47988, 47989, 47990, 47991, 47993, 47994, 47996, 47998, 47999, 48000, 48001, 48003, 48005, 48007, 48008, 48009, 48010, 48011, 48012, 48013, 47983, 48014, 48015, 48016, 48017, 48019, 48020, 48022, 48023, 48024, 48026, 47606, 48027, 48029, 48030, 48031, 48033, 48034, 48035, 48036, 48038, 48040, 48041, 48042, 48043, 48002, 48044, 48046, 48047, 48048, 48050, 48052, 48053, 48055, 48056, 48057, 48058, 48059, 48060, 48061, 48063, 48064, 48065, 48066, 48068, 48069, 48070, 48021, 48073, 48074, 48075, 48076, 48077, 48078, 48079, 48080, 48081, 48082, 48083, 48028, 48084, 48085, 48086, 48087, 48088, 48089, 48090, 48091, 48092, 48093, 48094, 48095, 48096, 48097, 48037, 48098, 48100, 48039, 48049, 48102, 48104, 48106, 48067, 48108, 48110, 48112, 48114, 48116, 47995, 48118, 48120, 48124, 48126, 48128, 48130, 48132, 48134, 48136, 48138, 48140, 48144, 48148, 48150, 48152, 48154, 48156, 48158, 48160, 48162, 48164, 48166, 48168, 48170, 48172, 48176, 48178, 48180, 48182, 48184, 48186, 48188, 48190, 48194, 48202, 48204, 47611, 48206, 48208, 48210, 48212, 48214, 48216, 48218, 48220, 48222, 48224, 48226, 48228, 48230, 48232, 48236, 48045, 48240, 48242, 48244, 48246, 48248, 48252, 48254, 48258, 48260, 48262, 48264, 48266, 48268, 48270, 48276, 48278, 48280, 48282, 48284, 48286, 48288, 48290, 48292, 48192, 48296, 48196, 48298, 48200, 48300, 48302, 48304, 48306, 48308, 48310, 48312, 48314, 48316, 48320, 48322, 48324, 48326, 48328, 48330, 48334, 48336, 48338, 48340, 48342, 48344, 48346, 48348, 48350, 48352, 48354, 48356, 48358, 48360, 48362, 48364, 48366, 48368, 48370, 48272, 48372, 48374, 48376, 48378, 48380, 48382, 48384, 48386, 48388, 48392, 48394, 48396, 48398, 48400, 48402, 48404, 48406, 48408, 48410, 48318, 48416, 48418, 48420, 48422, 48424, 48426, 48428, 48430, 48432, 48174, 48390, 48434, 48436, 48438, 48440, 48442, 48198, 48444, 48446, 48448, 48450, 48452, 48454, 48456, 48458, 48460, 48462, 48466, 48468, 48470, 48472, 48476, 48478, 48480, 48482, 48484, 48486, 48488, 48234, 48490, 48492, 48494, 48496, 48498, 48500, 48502, 48508, 48510, 48256, 48512, 48514, 48516, 48518, 48520, 48522, 48524, 48526, 48530, 48534, 48536, 48538, 48540, 48542, 48546, 48548, 48552, 48554, 48072, 48558, 48560, 48562, 48564, 48294, 48566, 48568, 48570, 48572, 48574, 48578, 48580, 48584, 48586, 48588, 48590, 48592, 48594, 48596, 48332, 48598, 48600, 48602, 48604, 48606, 48610, 48612, 48614, 48618, 48620, 48622, 48624, 48626, 48506, 48628, 48630, 48632, 48634, 48636, 48528, 48638, 48640, 48642, 48644, 48646, 48648, 48650, 48652, 48654, 48656, 48658, 48660, 48662, 48664, 48666, 48668, 48670, 48672, 48676, 48678, 48684, 48686, 48690, 48692, 48694, 48696, 48704, 48706, 48708, 48710, 48712, 48714, 48716, 48718, 48720, 48727, 48730, 48732, 48736, 48738, 48740, 48742, 48474, 48744, 48746, 48616, 48750, 48752, 48754, 48756, 48760, 48762, 48504, 48764, 48766, 48768, 48770, 48772, 48774, 48776, 48778, 48780, 48782, 48532, 48544, 48786, 48788, 48790, 48794, 48798, 48802, 48804, 48808, 48810, 48812, 48814, 48816, 48820, 48822, 48824, 48826, 48828, 48830, 48832, 48834, 48836, 48582, 48734, 48840, 48842, 48844, 48608, 48846, 48848, 48850, 48852, 48854, 48856, 48858, 48860, 48748, 48862, 48866, 48868, 48758, 48872, 48874, 48876, 48878, 48880, 48882, 48886, 48888, 48890, 48892, 48894, 48896, 48792, 48900, 48796, 48902, 48904, 48906, 48674, 48806, 48818, 48914, 48916, 48918, 48920, 48922, 48924, 48926, 48928, 48688, 48930, 48700, 48934, 48702, 48838, 48936, 48938, 48940, 48942, 48944, 48946, 48950, 48953, 48955, 48959, 48963, 48965, 48967, 48969, 48971, 48975, 48977, 48979, 48981, 48983, 48985, 48989, 48991, 48993, 48995, 48996, 48997, 48998, 49000, 49109, 49135, 49181, 48884, 49252, 49259, 49366, 49368, 49370, 49373, 49415, 48898, 49518, 49520, 49526, 49552, 49572, 49585, 48910, 49587, 49589, 48932, 49590, 49591, 49592, 49593, 49594, 49595, 49596, 49600, 48987, 49601, 49602, 48999, 49603, 49605, 49607, 49608, 48784, 49609, 49610, 49611, 49612, 49613, 49614, 49615, 49616, 49617, 49586, 49619, 49621, 49622, 49623, 49624, 49625, 49588, 49626, 49628, 49629, 49630, 49631, 49632, 49633, 49634, 49635, 49636, 49637, 49638, 49640, 49641, 49642, 49643, 49644, 49645, 49646, 49647, 49649, 49650, 49651, 49652, 49597, 49653, 49599, 49654, 49655, 49656, 49657, 49658, 48864, 49659, 49660, 49661, 49662, 48870, 49664, 49665, 49666, 49667, 49668, 49669, 49671, 49672, 49674, 49675, 49676, 49677, 49678, 49679, 49680, 49682, 49683, 49684, 49685, 49686, 49687, 49689, 49690, 49691, 49692, 48908, 49694, 49695, 49698, 49699, 49700, 49627, 49701, 49703, 49704, 49705, 49706, 49707, 49708, 49709, 49710, 49711, 49713, 49714, 49715, 49716, 49717, 49718, 49719, 49720, 49721, 49722, 49723, 49724, 49725, 49726, 49728, 49729, 49730, 49731, 49732, 49733, 49735, 49736, 49739, 49740, 49741, 49742, 49743, 49744, 49745, 49747, 49748, 49749, 49750, 49670, 49751, 49752, 49367, 49753, 49754, 49755, 49756, 49757, 49758, 49759, 49761, 49762, 49763, 49764, 49765, 49766, 49767, 49769, 49770, 49772, 49773, 49775, 49776, 49777, 49778, 49779, 49780, 49781, 49782, 49783, 49786, 49738, 49784, 49787, 49788, 49810, 49856, 49947, 49949, 49950, 49951, 49618, 49952, 49953, 49954, 49955, 49956, 49957, 49958, 49959, 49960, 49962, 49639, 49964, 49965, 49966, 49967, 49968, 49969, 49971, 49972, 49973, 49974, 49975, 49976, 49977, 49978, 49979, 49980, 49981, 49982, 49983, 49984, 49663, 49986, 49987, 49988, 49989, 49990, 49991, 49992, 49993, 49994, 49995, 49996, 49998, 49999, 50000, 50001, 50004, 50005, 50006, 50007, 49681, 49948, 50009, 50010, 50012, 50013, 50014, 50015, 50018, 50019, 50020, 50021, 49620, 48912, 50022, 50023, 50024, 50026, 50027, 50028, 50029, 50030, 50031, 50032, 50033, 50034, 50035, 50036, 50037, 50038, 50039, 50040, 50041, 50042, 50043, 50044, 50045, 50046, 50047, 50048, 50049, 50050, 50051, 50052, 50053, 50054, 50058, 50059, 50061, 50062, 49727, 50063, 50064, 50065, 50066, 50067, 50068, 50069, 50071, 50072, 50073, 50074, 50075, 50076, 50077, 50079, 50083, 50084, 50085, 50086, 50087, 50088, 50089, 50091, 50092, 50093, 50094, 50095, 50097, 50099, 50017, 50100, 50101, 50102, 50103, 50105, 50106, 50107, 50108, 50109, 50111, 50112, 50113, 50114, 50115, 50116, 50117, 50118, 50119, 50120, 50121, 50122, 50124, 50125, 50126, 50060, 49598, 50127, 50128, 50129, 50130, 50131, 50132, 50133, 50134, 50081, 50082, 50135, 50136, 50137, 50138, 50139, 50141, 50143, 49970, 50144, 50145, 50148, 50149, 50150, 50151, 50152, 50153, 50154, 50155, 50157, 50158, 50159, 50160, 50161, 50162, 50163, 50164, 50165, 50166, 50167, 50168, 50169, 50170, 50171, 50172, 50173, 50174, 50175, 50176, 50177, 50180, 50181, 50182, 50183, 50184, 50185, 50187, 50188, 50189, 50190, 50191, 50192, 50193, 50195, 50196, 50198, 50199, 50057, 50200, 50201, 50202, 50204, 50205, 50206, 50207, 50208, 50209, 50210, 50211, 50156, 50212, 50213, 50214, 50215, 50216, 50217, 50218, 50219, 50220, 50221, 50090, 50222, 50223, 50224, 50225, 50226, 50227, 50228, 50098, 50229, 50230, 50231, 50232, 50233, 50234, 50235, 50236, 50237, 50238, 50239, 50240, 50241, 50242, 50243, 50245, 50186, 50246, 50247, 50248, 50249, 50250, 50251, 50252, 50254, 50255, 50257, 50197, 50259, 50260, 50123, 50261, 50262, 50263, 50264, 50265, 50266, 50267, 50268, 50269, 50270, 50271, 50272, 50273, 50274, 50275, 50276, 50277, 50278, 50279, 50281, 50282, 50284, 50285, 50286, 50287, 50288, 50290, 50291, 50293, 50294, 50295, 50244, 50296, 50297, 50256, 50146, 50299, 50280, 50301, 50302, 50303, 50304, 50305, 50306, 50308, 50310, 50311, 50312, 50313, 50314, 50315, 50316, 50317, 50319, 50320, 50321, 50322, 50323, 50324, 50325, 50326, 50328, 50329, 50330, 50331, 50332, 50333, 50334, 50335, 50336, 50337, 50338, 50339, 50340, 50341, 50342, 50343, 50345, 50346, 50348, 50349, 50350, 50351, 50352, 50309, 50353, 50354, 50355, 50357, 50358, 50359, 50360, 50318, 50361, 50364, 50365, 50258, 50366, 50367, 50368, 50369, 50370, 50371, 50372, 50375, 50377, 50378, 50379, 50383, 50384, 50385, 50386, 50387, 50389, 50390, 50391, 50392, 50393, 50394, 50395, 50401, 50405, 50408, 50410, 50413, 50415, 50417, 50428, 50430, 50432, 50436, 50440, 50442, 50444, 50446, 50448, 50450, 50452, 50142, 50458, 50363, 50460, 50462, 50464, 50300, 50466, 50469, 50473, 50477, 50479, 50380, 50481, 50484, 50487, 50490, 50396, 50403, 50498, 50500, 50419, 50502, 50589, 50592, 50327, 50594, 50596, 50598, 50600, 50602, 50604, 50606, 50471, 50608, 50610, 50612, 50614, 50616, 50618, 50620, 50624, 50626, 50628, 50630, 50632, 50636, 50638, 50640, 50642, 50644, 50648, 50650, 50654, 50657, 50661, 50663, 50665, 50667, 50669, 50671, 50673, 50675, 50679, 50682, 50684, 50688, 50690, 50110, 50692, 50694, 50696, 50698, 50700, 50702, 50704, 50708, 50362, 50710, 50712, 50714, 50716, 50718, 50720, 50722, 50726, 50728, 50730, 50732, 50734, 50738, 50740, 50742, 50744, 50746, 50751, 50753, 50755, 50381, 50757, 50759, 50761, 50763, 50765, 50767, 50769, 50772, 50774, 50776, 50778, 50780, 50782, 50784, 50786, 50788, 50399, 50652, 50790, 50792, 50794, 50797, 50799, 50421, 50803, 50806, 50810, 50812, 50677, 50814, 50816, 50818, 50820, 50824, 50826, 50438, 50828, 50830, 50834, 50836, 50838, 50840, 50842, 50844, 50846, 50848, 50850, 50852, 50854, 50856, 50860, 50862, 50864, 50866, 50870, 50872, 50874, 50876, 50878, 50881, 50883, 50885, 50887, 50889, 50891, 50893, 50895, 50897, 50899, 50901, 50903, 50910, 50912, 50914, 50921, 50388, 50927, 50932, 50801, 50949, 50956, 50962, 50969, 50975, 50905, 50979, 50981, 50986, 50916, 50918, 50994, 50996, 50998, 51000, 50923, 50925, 51002, 50347, 51007, 51009, 50934, 51013, 50937, 50939, 51015, 51019, 50941, 51022, 50944, 50946, 50686, 50706, 51033, 51035, 51041, 50959, 51054, 51060, 50964, 50967, 51062, 51064, 50971, 50973, 51066, 51068, 50736, 51075, 51085, 51087, 51089, 51091, 51093, 51095, 50983, 50988, 51102, 51104, 51106, 51005, 50929, 51011, 51109, 51111, 51017, 51113, 51123, 51125, 51024, 51127, 51131, 50832, 51135, 51137, 51139, 51143, 51027, 51030, 51145, 51147, 51149, 51151, 51037, 51039, 50858, 51156, 51044, 51047, 51050, 51166, 51056, 51168, 51176, 51178, 51070, 51073, 51180, 51077, 51079, 51082, 51184, 51188, 50908, 51200, 51097, 51100, 51204, 51211, 51213, 51215, 51223, 51225, 51229, 51233, 51247, 51254, 51258, 51262, 51115, 51117, 51119, 51121, 51275, 51129, 51133, 51285, 51141, 51289, 51298, 51153, 51158, 51160, 51302, 51164, 51170, 51174, 51307, 51182, 51192, 51313, 51197, 51202, 51206, 51209, 51217, 51221, 51227, 51231, 51318, 51320, 51322, 51235, 51237, 51240, 51242, 51244, 51324, 51326, 51249, 51328, 51256, 51330, 51260, 51264, 51266, 51340, 51269, 51272, 51278, 51280, 51346, 51354, 51356, 51291, 51294, 51360, 51362, 51364, 51296, 51366, 51300, 51370, 51374, 51376, 51378, 51304, 51385, 51387, 51397, 51186, 51310, 51315, 51410, 51412, 51417, 51421, 51423, 51440, 51442, 51332, 51334, 51336, 51454, 51456, 51458, 51342, 51344, 51348, 51352, 51358, 51475, 51477, 51479, 51481, 51483, 51368, 51486, 51372, 51489, 51380, 51491, 51493, 51383, 51500, 51505, 51389, 51507, 51509, 51511, 51513, 51392, 51518, 51395, 51522, 51524, 51526, 51399, 51532, 51534, 51195, 51401, 51536, 51538, 51404, 51407, 51540, 51414, 51542, 51544, 51549, 51425, 51427, 51554, 51556, 51561, 51429, 51563, 51565, 51252, 51567, 51569, 51432, 51435, 51437, 51571, 51573, 51575, 51444, 51447, 51449, 51460, 51580, 51582, 51586, 51589, 51463, 51591, 51468, 51600, 51602, 51604, 51606, 51472, 51612, 51616, 51620, 51622, 51624, 51495, 51630, 51498, 51634, 51502, 51636, 51640, 51515, 51642, 51644, 51646, 51528, 51657, 51659, 51661, 51663, 51546, 51667, 51551, 51558, 51680, 51577, 51584, 51593, 51596, 51598, 51608, 51470, 51610, 51618, 51704, 51709, 51713, 51626, 51628, 51715, 51632, 51725, 51638, 51648, 51651, 51655, 51731, 51665, 51737, 51739, 51741, 51669, 51743, 51745, 51747, 51671, 51675, 51751, 51682, 51755, 51757, 51759, 51761, 51684, 51767, 51772, 51777, 51779, 51781, 51787, 51791, 51694, 51283, 51698, 51809, 51811, 51815, 51700, 51817, 51702, 51821, 51706, 51823, 51825, 51827, 51831, 51833, 51717, 51720, 51837, 51722, 51839, 51841, 51727, 51845, 51847, 51530, 51852, 51854, 51856, 51729, 51858, 51733, 51862, 51735, 51866, 51870, 51877, 51749, 51885, 51753, 51889, 51891, 51893, 51900, 51902, 51763, 51765, 51769, 51775, 51785, 51789, 51793, 51795, 51934, 51936, 51938, 51940, 51942, 51799, 51944, 51802, 51946, 51806, 51950, 51952, 51956, 51813, 51958, 51962, 51819, 51964, 51966, 51976, 51980, 51982, 51984, 51988, 51991, 51996, 52002, 52004, 52008, 52010, 52012, 52014, 51843, 52021, 52023, 51849, 52027, 52032, 51653, 52034, 52038, 52041, 52049, 52054, 51864, 52058, 52060, 51868, 52062, 52066, 51872, 52070, 51875, 52072, 51879, 51882, 51887, 52079, 52082, 52084, 52088, 51895, 51904, 51906, 51908, 51910, 51912, 51915, 52094, 52096, 51918, 51920, 51923, 52098, 51925, 51928, 51930, 51932, 51948, 51954, 51960, 51968, 51970, 52102, 51974, 52104, 51978, 51986, 51993, 51829, 51998, 52000, 52113, 52016, 52018, 52115, 52025, 52029, 52006, 52036, 52046, 52051, 52056, 52123, 52125, 52129, 52134, 52074, 52076, 52090, 52138, 52092, 52140, 52144, 51466, 52146, 51797, 52148, 52150, 52152, 52154, 52156, 52161, 52106, 52167, 52108, 52111, 52178, 52180, 52184, 51520, 52191, 52193, 52204, 52119, 52121, 52206, 52208, 52127, 52213, 52131, 52136, 52222, 52224, 52142, 52230, 52232, 52234, 52241, 52243, 52245, 52158, 52247, 52163, 52252, 52254, 52165, 52169, 52172, 52264, 52266, 52268, 52270, 52175, 52182, 52274, 52188, 52195, 52197, 52199, 52202, 52278, 52282, 52284, 52286, 52288, 52210, 52215, 52218, 52295, 52297, 52299, 52303, 52305, 52226, 52236, 52239, 52313, 52249, 52256, 52258, 52260, 52262, 52272, 52290, 52293, 52332, 52307, 52337, 52339, 52310, 52343, 52317, 52319, 52355, 52357, 52359, 52321, 52364, 52324, 52327, 52329, 52372, 52374, 52376, 52380, 52334, 52341, 52393, 52395, 52404, 52420, 52348, 52351, 52426, 52428, 52432, 52361, 52366, 52369, 52441, 52443, 52445, 52447, 52449, 52454, 52456, 52461, 52463, 52301, 52468, 52383, 52385, 52472, 52388, 52474, 52478, 52397, 52480, 52399, 52402, 52406, 52408, 52484, 52491, 52411, 52497, 52417, 52422, 52500, 52424, 52430, 52504, 52506, 52508, 52510, 52514, 52435, 52438, 52522, 52451, 52458, 52465, 52470, 52390, 52529, 52531, 52482, 52486, 52488, 52493, 52495, 52502, 52540, 52512, 52516, 52519, 52553, 52524, 52557, 52562, 52564, 52526, 52567, 52569, 52571, 52573, 52538, 52578, 52580, 52582, 52584, 52588, 52592, 52548, 52594, 52603, 52605, 52611, 52616, 52618, 52555, 52620, 52559, 52624, 52628, 52635, 52637, 52644, 52647, 52575, 52652, 52656, 52662, 52664, 52666, 52668, 52674, 52590, 52676, 52678, 52680, 52682, 52684, 52596, 52598, 52601, 52686, 52694, 52609, 52613, 52707, 52709, 52711, 52713, 52622, 52626, 52630, 52632, 52718, 52639, 52641, 52649, 52654, 52733, 52735, 52660, 52743, 52670, 52672, 52688, 52691, 52751, 52698, 52700, 52704, 52767, 52715, 52774, 52722, 52725, 52534, 52783, 52737, 52740, 52787, 52789, 52745, 52795, 52800, 52749, 52753, 52802, 52804, 52806, 52808, 52759, 52765, 52810, 52812, 52814, 52816, 52769, 52821, 52823, 52827, 52831, 52776, 52836, 52778, 52838, 52781, 52785, 52793, 52847, 52851, 52853, 52857, 52797, 52865, 52867, 52869, 52874, 52878, 52818, 52891, 52896, 52825, 52898, 52900, 52911, 52914, 52829, 52833, 52920, 52926, 52842, 52947, 52849, 52855, 52859, 52955, 52863, 52871, 52876, 52959, 52880, 52882, 52961, 52965, 52884, 52887, 52889, 52970, 52893, 52904, 52906, 52909, 52916, 52918, 52985, 52922, 52924, 52989, 52928, 52931, 52934, 52991, 52937, 52939, 52941, 52995, 52999, 52945, 52949, 52952, 52957, 53013, 52963, 52967, 53017, 53019, 53021, 53032, 53034, 53036, 53042, 53044, 53046, 52983, 53048, 53050, 53054, 52987, 53070, 53074, 53076, 52997, 53002, 53086, 53006, 53088, 53008, 53090, 53092, 53094, 53099, 53101, 53010, 53105, 53015, 53108, 53113, 53115, 53117, 53023, 53025, 53028, 53030, 53128, 53133, 53135, 53038, 53052, 53141, 53057, 53145, 53066, 53149, 53153, 53068, 53072, 53155, 53157, 53160, 52845, 53165, 53167, 53169, 53080, 53177, 53084, 53179, 53185, 53189, 53191, 53096, 53196, 53201, 53203, 53205, 53207, 53211, 53103, 53110, 53217, 53219, 53121, 53123, 53126, 53131, 53222, 53224, 53226, 53228, 53232, 53137, 53139, 53147, 53151, 53239, 53241, 53243, 53171, 53174, 53248, 53181, 53183, 53187, 53193, 53198, 53209, 53215, 53253, 53257, 53259, 53230, 53235, 53261, 53237, 53265, 53267, 53269, 53162, 53271, 53273, 53275, 53277, 53283, 53285, 53287, 53291, 53293, 53263, 53298, 53308, 53318, 53082, 53329, 53333, 53281, 53335, 53339, 53341, 53343, 53289, 53349, 53296, 53300, 53303, 53305, 53310, 53313, 53316, 53321, 53326, 53331, 53337, 53345, 53362, 53352, 53698, 53764, 53358, 53821, 53831, 53833, 53840, 53845, 53847, 53849, 53809, 53813, 53815, 53856, 53858, 53862, 53819, 53868, 53823, 53826, 53829, 53872, 53874, 53880, 53882, 53884, 53835, 53838, 53890, 53895, 53897, 53901, 53903, 53842, 53905, 53907, 53851, 53911, 53913, 53864, 53866, 53870, 53917, 53886, 53892, 53899, 53909, 53915, 53929, 53931, 53944, 53948, 53950, 53955, 53927, 53878, 53967, 53933, 53969, 53936, 53938, 53971, 53940, 53973, 53946, 53952, 53975, 53979, 53957, 53959, 53981, 53961, 53985, 53963, 53994, 53998, 54000, 53965, 54002, 54004, 54009, 53991, 53996, 54006, 54021, 54023, 54028, 54030, 54034, 54038, 54013, 54040, 54015, 54055, 54018, 54025, 54064, 54066, 54032, 54036, 54044, 54049, 54051, 54093, 54095, 54057, 54059, 54011, 54068, 53989, 54071, 54074, 54078, 54081, 54088, 54103, 54109, 54097, 54116, 54120, 54122, 54106, 54124, 54127, 54131, 54114, 54134, 54140, 54118, 54144, 54061, 54153, 54129, 54157, 54161, 54165, 54167, 54169, 54171, 54138, 54181, 54142, 54147, 54496, 54149, 54595, 54151, 54609, 54677, 54719, 54749, 54955, 54968, 55018, 55234, 55248, 54159, 55334, 54163, 55487, 55501, 55583, 55589, 55607, 55609, 55641, 54176, 55643, 54178, 55645, 55647, 55649, 54203, 55651, 55653, 54214, 54234, 55658, 54280, 55660, 55662, 55668, 55670, 55672, 55681, 55685, 55687, 55689, 55691, 54550, 55693, 55695, 54581, 55697, 54667, 55699, 54111, 55701, 54811, 54916, 55705, 55711, 55129, 55136, 55153, 55163, 55713, 55210, 55227, 55717, 55725, 55368, 55390, 55733, 55737, 55739, 55511, 55527, 55747, 55749, 55639, 55755, 55765, 55655, 55771, 55773, 55775, 55780, 55784, 55788, 55790, 55792, 55676, 55794, 55796, 55798, 55678, 55802, 55804, 55806, 55808, 55812, 55814, 55820, 55822, 55703, 55824, 55826, 55828, 55830, 55832, 55834, 55836, 55707, 55709, 55840, 55845, 55847, 55849, 55851, 55853, 55855, 55857, 55859, 55719, 55721, 55863, 55727, 55865, 55867, 55731, 55735, 55875, 55741, 55744, 55753, 55757, 55759, 55762, 56125, 56147, 56262, 56272, 55777, 56303, 56305, 56313, 55782, 55786, 55800, 55810, 56318, 55818, 56321, 56347, 56437, 55842, 55861, 56533, 55871, 55873, 55921, 55936, 56541, 56543, 56545, 56549, 56551, 56233, 56243, 56553, 56560, 56311, 56567, 56569, 56571, 56573, 56577, 56579, 56581, 56583, 56589, 56591, 56605, 56608, 56614, 56531, 56618, 56620, 56622, 56626, 56628, 56535, 56630, 56632, 56537, 56634, 56636, 56638, 56640, 56645, 56647, 56649, 56555, 56557, 56562, 56564, 56654, 56660, 56575, 56664, 56585, 56587, 56666, 56671, 56593, 56683, 56687, 56596, 56689, 56598, 56600, 56603, 56610, 56702, 56704, 56708, 56616, 56721, 56723, 56725, 56729, 56624, 56733, 56740, 56744, 56746, 56748, 56750, 56755, 56759, 56763, 56771, 56773, 56775, 56642, 56782, 56799, 56802, 56651, 56805, 56808, 56656, 56819, 56824, 56658, 56828, 56850, 56668, 56673, 56675, 56678, 56857, 56681, 56685, 56691, 56863, 56693, 56866, 56872, 56698, 55838, 56700, 56875, 56706, 56878, 56710, 56712, 56714, 56716, 56718, 56727, 56731, 56735, 56737, 56742, 56752, 56757, 56761, 56892, 56895, 56766, 56780, 56784, 56904, 56786, 56789, 56791, 56793, 56911, 56796, 56918, 56811, 56835, 56838, 56927, 56934, 56842, 56846, 56853, 56941, 56951, 56954, 56957, 56960, 56969, 56869, 56972, 56982, 56881, 56988, 56991, 56994, 57008, 57015, 56886, 57018, 57024, 56889, 57036, 56769, 56898, 57042, 56901, 57051, 57055, 57058, 57061, 56907, 57067, 56914, 57071, 57074, 57083, 57086, 57093, 57096, 57099, 57102, 57108, 56921, 56930, 57119, 57135, 57138, 57141, 57144, 56938, 56945, 57154, 57160, 57164, 56963, 56966, 57171, 56975, 56979, 57177, 57183, 57186, 56985, 57199, 57209, 57215, 57000, 57004, 57221, 57224, 57230, 57239, 57011, 57246, 57027, 57030, 57033, 57039, 57045, 57064, 57077, 57080, 57089, 57105, 57111, 57115, 57122, 57125, 57131, 57150, 57157, 57167, 57174, 57190, 57196, 57202, 57212, 57218, 57227, 57233, 57236, 57249, 57252, 56924, 29822, 52345, 53279
        );

        echo "<pre>";
        $list_order_exists = \DB::table('woo_orders')->pluck('order_id')->toArray();
        echo sizeof($full_order) - sizeof($list_order_exists)." >= ";

        $list_order_thieu = array_diff($full_order, $list_order_exists);
        echo sizeof($list_order_thieu)."\n";

        $info = \DB::table('woo_infos')->select('*')->where('id',4)->first();
        $woocommerce = $this->getConnectStore($info->url, $info->consumer_key, $info->consumer_secret);
        foreach ($list_order_thieu as $order_id)
        {
            try {
                $try = true;
                $result = ($woocommerce->get('orders/'.$order_id));
            } catch (\Exception $e)
            {
                $try = false;
            }
            if ($try)
            {
                $result = json_decode(json_encode($result, true), true);
                logfile_system('-- Tồn tại order Id: '. $order_id);
                $this->createOrder($result, $info->id);
            } else {
                logfile_system('-- Không tồn tại order Id: '. $order_id);
            }
        }
    }

    public function changeNameProduct()
    {
        $store_id = 4;
        $products = \DB::table('woo_products')->select('id','product_id')
            ->where('woo_info_id',$store_id)->get()->toArray();
        $info = \DB::table('woo_infos')->select('*')->where('id',$store_id)->first();
        $woocommerce = $this->getConnectStore($info->url, $info->consumer_key, $info->consumer_secret);
        foreach ($products  as $item)
        {
            $product_id = $item->product_id;
            try {
                $try = true;
                $result = ($woocommerce->get('products/'.$product_id));
            } catch (\Exception $e)
            {
                $try = false;
            }
            if ($try)
            {
                $result = json_decode(json_encode($result, true), true);
                logfile_system('-- Tồn tại product Id: '. $product_id);
                \DB::table('woo_orders')->where('woo_info_id',$store_id)->where('product_id',$product_id)->
                    update(['product_name' => $result['name']]);
                \DB::table('woo_products')->where('woo_info_id',$store_id)->where('product_id',$product_id)->
                    update(['name'  => $result['name'], 'permalink' => $result['permalink']]);
            } else {
                logfile_system('-- Không tồn tại product Id: '. $product_id);
            }
        }
    }

    public function changeSkuWooOrder()
    {
        $store_id = 4;
        $woo_orders = \DB::table('woo_orders')
            ->select(
                'id','product_name', 'sku', 'variation_detail', 'variation_full_detail', 'product_id'
            )
            ->where('woo_info_id',$store_id)->get()->toArray();
        foreach ($woo_orders as $item)
        {
            $tmp = explode('-;-;-', $item->variation_full_detail);
            $tmp = array_filter($tmp);
            $tmp_detail = ltrim(str_replace($item->variation_detail,'',implode('-', $tmp)), '-');
            if (strlen($tmp_detail) > 0)
            {
                $str_sku = $tmp_detail;
            } else {
                $str_sku = '';
            }
            $sku = $this->getSku($store_id, $item->product_id, $item->product_name, $str_sku);
            if ($sku != $item->sku)
            {
                \DB::table('woo_orders')->where('id',$item->id)->update(['sku' => $sku]);
            }
        }
    }

    public function changeVaritaionWooOrder()
    {
        $store_id = 4;
        $variation_change = [
            'us10_eu42' => 'season_boots_us10_eu42',
            'us105_eu425' => 'season_boots_us10_5_eu42_5',
            'us11_eu43' => 'season_boots_us11_eu43',
            'us45_eu35' => 'season_boots_us4_5_eu35',
            'us65_eu37' => 'season_boots_us6_5_eu37',
            'us75_eu39' => 'season_boots_us7_5_eu39',
            'us85_eu40' => 'season_boots_us8_5_eu40',
            'us95_eu41' => 'season_boots_us9_5_eu41',
            'us11_eu45' => 'season_boots_us11_eu45',
            'us8_eu41' => 'season_boots_us8_eu41'
        ];
        $woo_orders = \DB::table('woo_orders')
            ->select(
                'id','number','product_name', 'sku', 'variation_detail', 'variation_full_detail', 'detail'
            )
            ->where('woo_info_id',$store_id)->get()->toArray();
        foreach ($woo_orders as $item)
        {
            $update = array();
            foreach ($variation_change as $variation_store => $variation_new)
            {
                if (strpos($item->variation_detail, $variation_store) !== false && strpos($item->product_name, "Season Boots") !== false)
                {
                    $variation_detail = str_replace($variation_store, $variation_new, $item->variation_detail);
                    $variation_full_detail = str_replace($variation_store, $variation_new, $item->variation_full_detail);
                    $detail = str_replace($variation_store, $variation_new, $item->detail);
                    $update = [
                        'variation_detail' => $variation_detail,
                        'variation_full_detail' => $variation_full_detail,
                        'detail' => $detail
                    ];
                    break;
                }
            }
            if (sizeof($update) > 0)
            {
                $result = \DB::table('woo_orders')->where('id',$item->id)->update($update);
                if ($result)
                {
                    logfile_system('-- Update thanh cong variation cua order '.$item->number);
                } else {
                    logfile_system('-- Update that bai variation cua order '.$item->number);
                }
            }
        }
    }

    public function imgThumbProduct()
    {
        $products = \DB::table('woo_products')
            ->select('id','woo_info_id','product_id', 'image')
            ->where('woo_info_id', 4)
            ->limit(4)
            ->get()->toArray();
        $data_update = array();
        if (sizeof($products) > 0)
        {
            foreach ($products as $item)
            {
                $tmp = explode(',', $item->image);
                $img_update = "";
                foreach( $tmp as $img)
                {
                    if (strpos($img, 'v1/thumb/') == false)
                    {
                        $extension = strtolower(pathinfo($img)['extension']);
                        $rand = strRandom();
                        $img_update .= env('URL_LOCAL').genThumb($item->woo_info_id.$item->product_id.'_'.$rand.'.'.$extension, $img, env('THUMB')) . ",";
                    }
                }
                $img_update = substr(trim($img_update), 0, -1);
                if (strlen($img_update) > 0)
                {
                    logfile_system('-- Tao file thumb thanh cong cua product id: '. $item->product_id);
                    $data_update[$item->id] = [
                        'image' => $img_update
                    ];
                }
            }
            if(sizeof($data_update) > 0)
            {
                foreach ($data_update as $woo_product_id => $data)
                {
                    $result = \DB::table('woo_products')->where('id',$woo_product_id)->update($data);
                    if ($result)
                    {
                        logfile_system('--- Đổi thumb của '.sizeof($data_update). ' thành công');
                    } else {
                        logfile_system('--- Đổi thumb của '.sizeof($data_update). ' thất bại');
                    }
                }
            }
            $return = false;
        } else {
            $return = true;
        }
        return $return;
    }

    /* End ham tam thoi sau nay se xoa*/

    /*End WooCommerce API*/

    /*cập nhật thông tin sản phẩm đối thủ mới liên tục*/
    public function checkTemplateScrap()
    {
        logfile_system('=== [Cập nhật các store scrap có sản phẩm mới hay không] ===============================');
        $result = \DB::table('woo_templates')->where('website_id',19)->update([
            'status' => 0,
            'updated_at' => date("Y-m-d H:i:s")
            ]);
        if($result)
        {
            // Cào website
            $this->call('scan:website');
        } else {
            logfile_system('-- [Error] Xảy ra lỗi không thể cập nhật lại template về new');
        }
    }


}
