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
                'timeout' => 400,
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
        logfile_system('---------------- [Payment Again]------------------');
        $lists = \DB::table('woo_orders')
            ->join('woo_infos', 'woo_orders.woo_info_id', '=', 'woo_infos.id')
            ->select(
                'woo_orders.id', 'woo_orders.woo_info_id', 'woo_orders.order_id', 'woo_orders.order_status',
                'woo_infos.url', 'woo_infos.consumer_key', 'woo_infos.consumer_secret', 'woo_orders.number'
            )
//            ->where('woo_orders.created_at', '>', date('Y-m-d', strtotime("-30 days")))
            ->where('woo_orders.status', '<>', env('STATUS_SKIP'))
            ->whereNotIn('woo_orders.order_status', order_status())
            ->get()->toArray();
        if (sizeof($lists) > 0) {
            $list_stores = array();
            foreach($lists as $item)
            {
                $list_stores[$item->woo_info_id]['info'] = [
                    'url' => $item->url,
                    'consumer_key' => $item->consumer_key,
                    'consumer_secret' => $item->consumer_secret
                ];
                $list_stores[$item->woo_info_id]['list_order'][$item->order_id] = [
                    'order_id' => $item->id,
                    'woo_order_id' => $item->order_id,
                    'order_status' => $item->order_status,
                    'number' => $item->number
                ];
            }

            $order_update = array();
            $order_error = array();
            // bat dau cap nhat
            foreach ($list_stores as $store_id => $list)
            {
                $info = $list['info'];
                $woocommerce = $this->getConnectStore($info['url'], $info['consumer_key'], $info['consumer_secret']);
                foreach ($list['list_order'] as $order)
                {
                    $order_id = $order['order_id'];
                    $woo_order_id = $order['woo_order_id'];
                    $order_status = $order['order_status'];
                    try {
                        $info = $woocommerce->get('orders/' . $woo_order_id);
                        logfile_system('--- Đã tìm thấy thông tin order : '.$order['number']);
                        $result = true;
                    } catch (\Exception $e) {
                        logfile_system('--- Không tìm thấy order : '.$order['number']);
                        $result = false;
                    }
                    if ($result)
                    {
                        $data = json_decode(json_encode($info, true), true);
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
                        \DB::table('woo_orders')
                            ->where('order_id',$woo_order_id)
                            ->where('woo_info_id', $store_id)
                            ->update($update);
                    } else {
                        $order_error[] = $order_id;
                    }
                }
            }
            if (sizeof($order_error) > 0)
            {
                // Cập nhật trạng thái order là bỏ qua
                \DB::table('woo_orders')->whereIn('id',$order_error)->update(['status' => env('STATUS_SKIP')]);
            }
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

    public function updateDesignId()
    {
        $status = 'success';
        $message = 'Đã chạy xong get Design Id';
        $result = $this->getDesignNew();
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
        $return = false;
        $rq = $request->all();
        if (isset($rq['store_id']))
        {
            $store_id = $rq['store_id'];
        } else {
            $store_id = $rq['id_store'];
        }
        $sku_auto_id = getSkuAutoId(trim($rq['auto_sku']));
        $t_status = env('TEMPLATE_STATUS_KEEP_TITLE');
        if(array_key_exists('template_tool_status', $rq) && $rq['template_tool_status'] == env('TEMPLATE_STATUS_REMOVE_TITLE'))
        {
            $t_status = env('TEMPLATE_STATUS_REMOVE_TITLE');
        }
        $template_id = $rq['id_product'];
        // nếu là scrap website
        if ($scrap == 1) {
            // Nếu k chọn web site thuộc flatform
            if ($rq['platform_id'] == 1) {
                if (isset($rq['website_id'])) {
                    $website_id = $rq['website_id'];
                } else {
                    $return = true;
                    $alert = 'error';
                    $message = 'Bạn cần phải chọn website từ WEB SELECT do bạn đang sử dụng crawler không phải platform đã có';
                }
            } else { // Chọn website cần crawler từ danh sách platform đã có
                $platform_id = $rq['platform_id'];
                // kiểm tra đã tồn tại website thuộc store đã chọn hay chưa
                $check_exist = \DB::table('websites')
                    ->where([
                        ['store_id', '=', $store_id],
                        ['url', '=', $rq['web_link']],
                        ['platform_id', '=', $platform_id]
                    ])->first();
                if ($check_exist == NULL) // nếu chưa tồn tại thì tạo mới
                {
                    $website_id = \DB::table('websites')->insertGetId([
                        'store_id' => $store_id,
                        'platform_id' => $platform_id,
                        'exclude_text' => $rq['text_exclude'],
                        'url' => $rq['web_link'],
                        'image_array' => trim($rq['image_choose']),
                        'keyword_import' => (isset($rq['keyword_import']) && $rq['keyword_import'] == 'on')? 1 : 0,
                        'first_title' => ($rq['first_title'] != '')? trim($rq['first_title']) : NULL,
                        'exclude_image' => ($rq['exclude_image'] != '')? trim($rq['exclude_image']) : NULL,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    $new_website_id = $website_id;
                    if (!$website_id) {
                        $return = true;
                        $alert = 'error';
                        $message = 'Xảy ra lỗi nội bộ. Không thể tạo được website ID để crawler. Mời bạn tải lại trang và thử lại.';
                    }
                } else { // nếu tồn tại rồi thì thoát ra
                    $return = true;
                    $alert = 'error';
                    $message = 'Bạn đã chọn website này crawler trước đó rồi. Mời thử website khác.';
                }
            }
        } else {
            $website_id = null;
        }
        if ($return) {
            return back()->with($alert, $message);
        } else {
            $result_category = false;
            $id_store = $store_id;
            $check_exist = \DB::table('woo_templates')
                ->where('template_id', $template_id)
                ->where('store_id', $id_store)
                ->select('template_path')
                ->first();
            // neu khong ton tai template id trong he thong.
            if (!is_null($check_exist)) {
                $template_path = $check_exist->template_path;
                $template_data = readFileJson($template_path);
                // lấy tên và id của category
                if (isset($template_data['categories'][0])) {
                    $tem_category = $template_data['categories'][0];
                    $category_name = $tem_category['name'];
                    $woo_category_id = $tem_category['id'];
                    $result_category = true;
                } else {
                    $category_name = null;
                    $woo_category_id = null;
                }
            } else {
                $woocommerce = $this->getConnectStore($rq['url'], $rq['consumer_key'], $rq['consumer_secret']);
                try {
                    $results = true;
                    $i = $woocommerce->get('products/' . $rq['id_product']);
                } catch (\Exception $e) {
                    $results = false;
                }
                if (!$results) {
                    // xóa website id vừa tạo
                    if (isset($new_website_id))
                    {
                        \DB::table('websites')->where('id',$new_website_id)->delete();
                    }
                    $alert = 'error';
                    $message = 'Không tìm thấy product Id '.$rq['id_product'].' ở store : ' . $rq['url'];
                    return back()->with($alert, $message);
                } else {
                    $r = $this->makeFileTemplate($i, $id_store, $template_id);
                    $template_data = json_decode(json_encode($i ,true),true);
                    $result = $r['result'];
                    $template_path = $r['template_path'];
                    $template_name = $r['template_name'];
                    $variation_list = $r['variation_list'];
                    $path = $r['path'];
                    // Nếu tạo file json thành công. Luu thông tin template vao database
                    if ($result) {
                        $woo_template_id = \DB::table('woo_templates')->insertGetId([
                            'product_name' => $template_name,
                            'template_id' => $template_id,
                            'store_id' => $id_store,
                            'website_id' => $website_id,
                            'template_path' => $template_path,
                            'sku_auto_id' => $sku_auto_id,
                            't_status' => $t_status,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                        // Quét thông tin variations gửi vào database
                        $insert_variation = array();
                        if (sizeof($variation_list) > 0) {
                            for ($j = 0; $j < sizeof($variation_list); $j++) {
                                $varid = $variation_list[$j];
                                $variation_path = $path . 'variation_' . $varid . '.json';
                                $variation_data = $woocommerce->get('products/' . $template_id . '/variations/' . $varid);
                                $result = writeFileJson($variation_path, $variation_data);
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
                        }
                        if (sizeof($insert_variation) > 0) {
                            \DB::table('woo_variations')->insert($insert_variation);
                        }
                    }
                    // lấy tên và id của category
                    if (isset($i->categories[0])) {
                        $tem_category = $i->categories[0];
                        $category_name = $tem_category->name;
                        $woo_category_id = $tem_category->id;
                        $result_category = true;
                    } else {
                        $category_name = null;
                        $woo_category_id = null;
                    }
                }
            }
            if ($result_category) {
                // kiểm tra với woo_categories có sẵn tại tool xem tồn tại chưa.
                $check_category = \DB::table('woo_categories')->select('id')
                    ->where([
                        ['name', '=', $category_name],
                        ['store_id', '=', $id_store]
                    ])->first();
                if ($check_category != NULL) {
                    $category_id = $check_category->id;
                } else {
                    $woocommerce = $this->getConnectStore($rq['url'], $rq['consumer_key'], $rq['consumer_secret']);
                    $data = ['slug' => $category_name];
                    // kết nối tới woocommerce store để lấy thông tin
                    $result = $woocommerce->get('products/categories', $data);
                    $woo_category_id = $result[0]->id;
                    $data = [
                        'woo_category_id' => $woo_category_id,
                        'name' => $category_name,
                        'slug' => $result[0]->slug,
                        'store_id' => $id_store,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                    $category_id = \DB::table('woo_categories')->insertGetId($data);
                }
                \DB::table('websites')->where('id',$website_id)->update(['woo_category_id' => $category_id]);
                $category_data = [
                    'category_id' => $category_id,
                    'category_name' => $category_name,
                    'woo_category_id' => $woo_category_id,
                    'sku_auto_id' => $sku_auto_id
                ];
                $template_tool_status = getTemplateStatus();
                $data = array();
                if ($scrap != null) {
                    return redirect('scrap-create-template')->with('success', 'Connect với template thành công');
                } else {
                    return view("/admin/woo/save_path_template",
                        compact('data', "template_data", 'rq', 'category_data', 'template_tool_status'));
                }
            } else {
                $alert = 'error';
                $message = 'Template không tìm thấy category. Kiểm tra lại template của website: ' . $rq['url'] . ' với product id: ' . $rq['id_product'];
                return back()->with($alert, $message);
            }
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

    public function uploadImageScrap()
    {
        logfile_system('== Đang up ảnh scrap product lên store');
        $return = false;
        $limit = 2;
        $list_scrap_id = \DB::table('scrap_products')
            ->select('id')
            ->where('status',1)
            ->whereNull('woo_slug')
            ->limit($limit)
            ->get()->toArray();
        if (sizeof($list_scrap_id) > 0)
        {
            $list_id = array();
            foreach ($list_scrap_id as $scrap_id)
            {
                $list_id[] = $scrap_id->id;
            }
            // lấy danh sách của ảnh cần được upload
            $lists = \DB::table('woo_image_uploads as wup')
                ->leftjoin('woo_infos', 'wup.store_id', '=', 'woo_infos.id')
                ->leftjoin('scrap_products as scp', 'wup.woo_scrap_product_id', '=', 'scp.id')
                ->select(
                    'wup.id', 'wup.url as image_url', 'wup.woo_scrap_product_id', 'wup.image_name', 'wup.store_id',
                    'scp.id as scrap_product_id', 'scp.woo_product_id', 'scp.woo_slug', 'scp.woo_product_name',
                    'woo_infos.url', 'woo_infos.consumer_key', 'woo_infos.consumer_secret'
                )
                ->whereIn('wup.woo_scrap_product_id',$list_id)
                ->get()->toArray();
            if (sizeof($lists) > 0)
            {
                $stores = array();
                $woo_product_id_empty = array();
                // gộp vào chung 1 store để gọi API.
                foreach ($lists as $item)
                {
                    if ($item->woo_product_id != '')
                    {
                        $stores[$item->store_id]['info'] = [
                            'url' => $item->url,
                            'consumer_key' => $item->consumer_key,
                            'consumer_secret' => $item->consumer_secret
                        ];
                        // gộp ảnh vào cùng 1 sản phẩm để upload cùng 1 lúc
                        $stores[$item->store_id]['data'][$item->woo_product_id.'_'.$item->scrap_product_id]['images'][] = [
                            'src' => $item->image_url,
                            'name' => $item->image_name,
                            'alt' => $item->image_name,
                        ];
                        // gộp woo slug
                        $stores[$item->store_id]['data'][$item->woo_product_id.'_'.$item->scrap_product_id]['woo_slug'] = $item->woo_slug;
                        $stores[$item->store_id]['data'][$item->woo_product_id.'_'.$item->scrap_product_id]['woo_product_name'] = $item->woo_product_name;
                        // gộp toàn bộ id của woo upload image vào 1 mảng để batch update
                        $stores[$item->store_id]['data'][$item->woo_product_id.'_'.$item->scrap_product_id]['id'][] = $item->id;
                    } else {
                        $woo_product_id_empty[] = $item->id;
                    }
                }
                // nếu không tồn tại sản phẩm cần được upload. Bỏ qua
                if (sizeof($woo_product_id_empty) > 0)
                {
                    \DB::table('woo_upload_images')->whereIn('id', $woo_product_id_empty)->update([
                        'status' => env('STATUS_SKIP')
                    ]);
                }
                if (sizeof($stores) > 0)
                {
                    $woo_image_upload_success = array();
                    $woo_image_upload_error = array();
                    foreach ($stores as $store_id => $item)
                    {
                        $store = $item['info'];
                        //Kết nối với woocommerce
                        $woocommerce = $this->getConnectStore($store['url'], $store['consumer_key'], $store['consumer_secret']);
                        foreach ($item['data'] as $key => $value)
                        {
                            $tmp = explode('_',$key);
                            $woo_product_name = $value['woo_product_name'];
                            $woo_product_id = $tmp[0];
                            $scrap_product_id = $tmp[1];
                            $images = $value['images'];
                            if ($value['woo_slug'] == '')
                            {
                                $data = array(
                                    'id' => $woo_product_id,
                                    'status' => 'publish',
                                    'images' => $images
                                );
                            } else {
                                $data = array(
                                    'id' => $woo_product_id,
                                    'images' => $images
                                );
                            }
                            $result = $woocommerce->put('products/' . $woo_product_id, $data);
                            try {
                                $try = true;
                                $result = $woocommerce->put('products/' . $woo_product_id, $data);
                            } catch (\Exception $e) {
                                $try = false;
                            }
                            if ($try)
                            {
                                $woo_slug = $result->permalink;
                                \DB::table('scrap_products')->where('id',$scrap_product_id)->update(['woo_slug' => $woo_slug]);
                                $woo_image_upload_success = array_merge($woo_image_upload_success, $value['id']);
                                logfile_system('-- Đã chuẩn bị thành công data của sản phẩm ' . $woo_product_name);
                            } else {
                                $woo_image_upload_error = array_merge($woo_image_upload_error, $value['id']);
                                logfile_system('-- Thất bại. Không chuẩn bị được data của sản phẩm ' . $woo_product_name);
                            }
                        }
                    }

                    if (sizeof($woo_image_upload_error) > 0)
                    {
                        \DB::table('woo_image_uploads')->whereIn('id',$woo_image_upload_error)->update(['status' => env('STATUS_WORKING_ERROR')]);
                    }

                    if (sizeof($woo_image_upload_success) > 0)
                    {
                        \DB::table('woo_image_uploads')->whereIn('id',$woo_image_upload_success)->update(['status' => 1]);
                    }
                }
            }
        } else {
            logfile_system('-- Đã hết ảnh để up lên store. Chuyển sang công việc khác.');
            $return = true;
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
                ['status', '=', 23],
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
                        'wopd.woo_category_id', 'wopd.sku_auto_string',
                        'woo_tags.woo_tag_id', 'woo_tags.name as tag_name', 'woo_tags.slug as tag_slug',
                        'woo_temp.template_path', 'woo_temp.t_status',
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
                        if ($val->t_status == env('TEMPLATE_STATUS_REMOVE_TITLE'))
                        {
                            $woo_product_name = ucwords($val->name).' '.$val->sku_auto_string;
                        } else {
                            $woo_product_name = ucwords($val->name) . ' ' . $template_json['name'].' '.$val->sku_auto_string;
                        }
                        logfile_system("-- Đang tạo sản phẩm mới : " . $woo_product_name);
                        $prod_data = $template_json;
                        $prod_data['name'] = $woo_product_name;
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
                                $tmp_path = 'img_google/' . $val->name . '/' .basename($file['path'])."_". $file['name'];
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
            $feed_id_error = array();
            $scrap_id_error = array();
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
                    try {
                        $result = ($woocommerce->get('products/' . $feed['woo_product_id']));
                    } catch (\Exception $e)
                    {
                        $result = false;
                    }
                    if ($result)
                    {
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
                    } else {
                        logfile_system(' --- Đang check feed id: ' . $feed['id'] . ' : Khong ton tai san pham nay');
                        $feed_id_error[] = $feed['id'];
                        $scrap_id_error[] = $feed['scrap_product_id'];
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

            if (sizeof($feed_id_error) > 0)
            {
                \DB::table('feed_products')->whereIn('id',$feed_id_error)->delete();
            }

            if (sizeof($scrap_id_error) > 0)
            {
                \DB::table('scrap_products')->whereIn('id',$scrap_id_error)->delete();
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
            $product_code = ($rq['product_code'] != '') ? trim($rq['product_code']) : NULL;
            if ($product_code != NULL)
            {
                $auto_sku = '';
            } else {
                $auto_sku = $rq['auto_sku'];
            }
            $sku_auto_id = getSkuAutoId(trim($auto_sku));
            $product_name = ucwords(trim($rq['product_name']));
            $sale_price = ($rq['sale_price'] != '') ? trim($rq['sale_price']) : 0;
            $origin_price = ($rq['origin_price'] != '') ? trim($rq['origin_price']) : 0;
            $product_name_exclude = ($rq['product_name_exclude'] != '') ? ucwords(trim($rq['product_name_exclude'])) : NULL;
            $product_name_change = ($rq['product_name_change'] != '') ? ucwords(trim($rq['product_name_change'])) : NULL;
            $id = trim($rq['id']);
            $woo_info = \DB::table('woo_templates')
                ->join('woo_infos', 'woo_templates.store_id', '=', 'woo_infos.id')
                ->select(
                    'woo_templates.template_id', 'woo_templates.template_path','woo_templates.sku_auto_id',
                    'woo_infos.id as store_id', 'woo_infos.url', 'woo_infos.consumer_key', 'woo_infos.consumer_secret'
                )
                ->where('woo_templates.id', $id)
                ->first();
            $check_variations_exist = \DB::table('woo_variations')
                ->select('variation_id', 'variation_path')
                ->where('woo_template_id', $id)
                ->get()->toArray();
            $result_variations = false;
            // Kiểm tra xem auto_sku mới có trùng với auto_sku cũ hay không.
            $result_change = false;
            if ($sku_auto_id != $woo_info->sku_auto_id)
            {
                $result_change = true;
            }
            // End kiểm tra xem auto_sku mới có trùng với auto_sku cũ hay không.
            try {
                $woocommerce = $this->getConnectStore($woo_info->url, $woo_info->consumer_key, $woo_info->consumer_secret);
                $template_old = readFileJson($woo_info->template_path);
                $update = [
                    'name' => $product_name,
                    'price' => ($sale_price > 0) ? $sale_price : $template_old['price'],
                    'regular_price' => ($origin_price > 0) ? $origin_price : $template_old['regular_price'],
                    'sale_price' => ($sale_price > 0) ? $sale_price : $template_old['sale_price']
                ];
                if (sizeof($check_variations_exist) > 0)
                {
                    foreach ($check_variations_exist as $item)
                    {
                        $update_variation[] = [
                            'id' => $item->variation_id,
                            'price' => ($sale_price > 0) ? $sale_price : $template_old['price'],
                            'regular_price' => ($origin_price > 0) ? $origin_price : $template_old['regular_price'],
                            'sale_price' => ($sale_price > 0) ? $sale_price : $template_old['sale_price']
                        ];
                    }
                    $data_update_variation['update'] = $update_variation;
                    $result_variations = $woocommerce->post('products/'.$woo_info->template_id.'/variations/batch', $data_update_variation);
                }
                $update_template = $woocommerce->put('products/' . $woo_info->template_id, $update);
                $try = true;
            } catch (\Exception $e) {
                $try = false;
            }
            if ($try) {
                $data_ud_variations = array();
                if ($result_variations)
                {
                    foreach ($result_variations->update as $item)
                    {
                        $result = $this->makeFileTemplate_variation($item, $woo_info->store_id, $woo_info->template_id, $item->id);
                        if ($result['result'])
                        {
                            $data_ud_variations[$item->id]['variation_path'] = $result['template_path'];
                        } else {
                            $message .= 'Variation ID: '.$item->id.' Không thể tạo mới được file '.$result['path']." <br>";
                        }
                    }
                }
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
                    'sku_auto_id' => $sku_auto_id,
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                if ($result) {
                    if (sizeof($data_ud_variations) > 0)
                    {
                        foreach ($data_ud_variations as $variation_id => $data_update)
                        {
                            \DB::table('woo_variations')
                                ->where('store_id', $woo_info->store_id)
                                ->where('template_id', $woo_info->template_id)
                                ->where('variation_id', $variation_id)
                                ->update($data_update);
                        }
                    }
                    $result_update = \DB::table('woo_templates')->where('id', $id)->update($update_db);
                    // Đang thay đổi woo driver product
                    if ($rq['website_id'] == '')
                    {
                        \DB::table('woo_product_drivers')
                            ->where('template_id',$woo_info->template_id)
                            ->where('store_id',$woo_info->store_id)
                            ->whereNotNull('woo_product_id')
                            ->update(['status_tool' => env('STATUS_TOOL_EDITING')]);
                    } else { // Đang thay đổi scrap product
                        \DB::table('scrap_products')
                            ->where('template_id',$woo_info->template_id)
                            ->where('store_id',$woo_info->store_id)
                            ->whereNotNull('woo_product_id')
                            ->update(['status_tool' => env('STATUS_TOOL_EDITING')]);
                    }

                    if ($result_update) {
                        var_dump($result_change);
                        // nếu sku auto thay đổi hoặc đổi sang sku fixed thì thay đổi toàn bộ
                        if ($result_change)
                        {
                            $this->startChangeSkuAuto($sku_auto_id, $woo_info->template_id, $woo_info->store_id, $rq['website_id']);
                        }
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

    // đổi trạng thái sku auto hoặc sku fixed
    private function startChangeSkuAuto($sku_auto_id, $template_id, $store_id, $website_id)
    {
        // Mặc định đưa trạng thái về trạng thái không dùng sku auto
        $where = [
            ['template_id', '=', $template_id],
            ['store_id', '=', $store_id]
        ];
        $update = [
            'sku_auto_string' => NULL,
            'updated_at' => date("Y-m-d H:i:s")
        ];
        // driver product
        if ($website_id == '') {
            \DB::table('woo_product_drivers')->where($where)->update($update);
        } else { // scrap product
            \DB::table('scrap_products')->where($where)->update($update);
        }
        // Nếu vẫn giữ sku auto
        if ($sku_auto_id != 0) {
            $tmp_sku = getInfoSkuName($sku_auto_id);
            print_r($tmp_sku);
            // driver product
            if ($website_id == '') {
                $products = \DB::table('woo_product_drivers')
                    ->select('id')
                    ->where($where)
                    ->get()->toArray();
            } else { // scrap product
                $products = \DB::table('scrap_products')
                    ->select('id')
                    ->where($where)
                    ->get()->toArray();
            }
            $i = 1;
            $update_data = array();
            foreach ($products as $p_id)
            {
                $sku_new = $tmp_sku['sku'].($tmp_sku['count']+$i).$tmp_sku['last_prefix'];
                $update_data[$p_id->id] = $sku_new;
                // driver product
                if ($website_id == '') {
                    \DB::table('woo_product_drivers')->where('id', $p_id->id)->update(['sku_auto_string' => $sku_new]);
                } else { // scrap product
                    \DB::table('scrap_products')->where('id', $p_id->id)->update(['sku_auto_string' => $sku_new]);
                }
                $i++;
            }
        }
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

    private function makeFileTemplate_variation($templates, $id_store, $template_id, $variation_id)
    {
        $template_data = json_decode(json_encode($templates), True);
        $description = htmlentities(str_replace("\n", "<br />", $template_data['description']));
        $template_data['description'] = $description;
        //tao thu muc de luu template
        $path = storage_path('app/public') . '/template/' . $id_store . '/' . $template_id . '/';
        makeFolder(($path));
        $count_file = sizeof(array_diff(scandir($path), array('.', '..'))) + 1;
        // Write File
        $template_path = $path . 'variation_' . $variation_id .'_'.$count_file. '.json';
        $template_data['meta_data'] = [];
        $result = writeFileJson($template_path, $template_data);
        chmod($template_path, 777);
        $return = [
            'result' => $result,
            'template_path' => $template_path,
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
                    'wtp.product_name_exclude', 'wtp.template_path', 'wtp.origin_price', 'wtp.sale_price'
                )
                ->where('scp.status_tool', env('STATUS_TOOL_EDITING'))
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
                            'price' => ($info_template['sale_price'] == '')? '' : $info_template['price'],
                            'regular_price' => $info_template['regular_price'],
                            'sale_price' => $info_template['sale_price']
                        ];
                        $data_update_variations = array();
                        try {
                            $result_change = $woocommerce->put('products/' . $item['woo_product_id'], $update);
                            $variations_id = $result_change->variations;
                            if (sizeof($variations_id) > 0)
                            {
                                foreach ($variations_id as $vari_id)
                                {
                                    $data_update_variations['update'][] = [
                                        'id' => $vari_id,
                                        'price' => $item['sale_price'],
                                        'regular_price' => $item['origin_price'],
                                        'sale_price' => $item['sale_price']
                                    ];
                                }
                                $result_variations = $woocommerce->post('products/'.$item['woo_product_id'].'/variations/batch', $data_update_variations);
                            }
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
                    \DB::table('scrap_products')->whereIn('id', $scrap_id_success)->update(['status_tool' => env('STATUS_TOOL_DEFAULT')]);
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


    public function getStructFolder($folder)
    {
        if(strlen($folder) > 0 && (strpos($folder, '_') !== false))
        {
            $tmp = explode('_', trim($folder));
            if(sizeof($tmp) == 2)
            {
                $number_order = $tmp[0];
                $woo_order_id = (int)($tmp[1]-5)/9;
                $check = \DB::table('file_fulfills')
                    ->leftjoin('working_files','file_fulfills.working_file_id', '=', 'working_files.id')
                    ->select('file_fulfills.web_path_file','working_files.thumb')
                    ->where('file_fulfills.order_number', $number_order)
                    ->where('file_fulfills.woo_order_id', $woo_order_id)
                    ->get()->toArray();
                if (sizeof($check) > 0)
                {
                    return view('/addon/show_file_fulfill',compact('check', 'number_order'));
                } else {
                    die("Error. Not Right.");
                }
            } else {
                die("Error. Not Right.");
            }
        } else {
            die("Error. Not Right.");
        }
    }
}
