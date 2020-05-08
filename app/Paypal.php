<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Paypal extends Model
{

    public $timestamps = true;
    protected $table = 'paypals';

    private $url = 'https://api.paypal.com/';
//    private $url = 'https://api.sandbox.paypal.com/';

    public function create($request)
    {
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $data = $rq;
            unset($data['_token']);
            $data['note'] = trim(htmlentities($rq['note']));
            $action = false;
            if (isset($rq['active']) && $rq['active'] == 'on') {
                unset($data['active']);
                $data['status'] = 1;
                $action = true;
            } else {
                $data['status'] = 0;
            }
            $store_id = $rq['store_id'];
            $date = date("Y-m-d H:i:s");
            $data['created_at'] = $date;
            $data['updated_at'] = $date;

            if ($action) {
                \DB::table('paypals')->where('store_id', $store_id)->update([
                    'status' => 0,
                    'updated_at' => $date
                ]);
            }
            $paypal_id = \DB::table('paypals')->insertGetId($data);
            if ($action) {
                \DB::table('woo_orders')
                    ->where([
                        ['woo_info_id', '=', $store_id],
                        ['payment_method', '=', 'Paypal'],
                        ['paypal_id', '=', 0]
                    ])
                    ->update([
                        'paypal_id' => $paypal_id,
                        'updated_at' => $date
                    ]);
            }
            $status = 'success';
            $message = 'Kết nối tài khoản paypal thành công.';
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $status = 'error';
            $message = 'Xảy ra lỗi. Hãy thử lại.';
            logfile($message . ' - ' . $e->getMessage());
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return redirect('paypal-connect')->with($status, $message);
    }

    public function edit17TrackCarrier($request)
    {
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $track_carrier_id = $rq['id'];
            $track_carrier_name = $rq['name'];
            $paypal_carrier_id = $rq['paypal_carrier_id'];
            $result = \DB::table('17track_carriers')->where('id', $track_carrier_id)->update([
                'paypal_carrier_id' => $paypal_carrier_id,
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            if ($result)
            {
                $status = 'success';
                $message = 'Cập nhật paypal carrier cho nhà cung cấp : '.$track_carrier_name.' thành công';
            } else {
                $status = 'error';
                $message = 'Cập nhật paypal carrier cho nhà cung cấp : '.$track_carrier_name.' thất bại';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $status = 'error';
            $message = 'Xảy ra lỗi. Hãy thử lại.';
            logfile($message . ' - ' . $e->getMessage());
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return redirect('carrier-select')->with($status, $message);
    }

    public function connect($clientId, $secret)
    {
        $json = array();
        $ch = curl_init();
        $url = $this->url;
        curl_setopt($ch, CURLOPT_URL, $url."v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

        $result = curl_exec($ch);
        curl_close($ch);

        if (empty($result)) die("Error: No response.");
        else {
            $json = json_decode($result);
        }
        return $json;
    }

    public function getNewTracking($lists, $database)
    {
        \DB::beginTransaction();
        try {
            foreach ($lists as $paypal) {
                $client_id = $paypal['client_id'];
                $client_secret = $paypal['client_secret'];
                $json_data = $this->connect($client_id, $client_secret);
                $access_token = $json_data->access_token;
                $data['trackers'] = $paypal['trackers'];
                $new_data = json_encode($data);
                $result = $this->addTracking($new_data, $access_token);
            }
            if ($result) {
                if (sizeof($database['new_shipped']) > 0) {
                    \DB::table('trackings')->whereIn('id', $database['new_shipped'])
                        ->update([
                            'payment_status' => env('TRACK_INTRANSIT')
                        ]);
                }
                if (sizeof($database['new_pickup']) > 0) {
                    \DB::table('trackings')->whereIn('id', $database['new_pickup'])
                        ->update([
                            'payment_status' => env('TRACK_PICKUP')
                        ]);
                }
                if (sizeof($database['new_delivered']) > 0) {
                    \DB::table('trackings')->whereIn('id', $database['new_delivered'])
                        ->update([
                            'payment_status' => env('TRACK_DELIVERED')
                        ]);
                }
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $status = 'error';
            $message = 'Xảy ra lỗi. Hãy thử lại.';
            logfile($message . ' - ' . $e->getMessage());
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
    }

    public function getUpdateTracking($lists)
    {
        \DB::beginTransaction();
        try {
            $update_pickup = $update_delivered = array();
            foreach ($lists as $paypal) {
                $client_id = $paypal['client_id'];
                $client_secret = $paypal['client_secret'];
                $json_data = $this->connect($client_id, $client_secret);
                $access_token = $json_data->access_token;
                foreach ($paypal['data'] as $dt) {
                    $update_data = $dt;
                    $tracking_id = $dt['tracking_id'];
                    unset($update_data['tracking_id']);
                    $update_data = json_encode($update_data);
                    $path = $dt['transaction_id'] . '-' . $dt['tracking_number'];
                    $json = $this->updateTracking($path, $update_data, $access_token);
                    if ($json) {
                        if ($dt['status'] == 'LOCAL_PICKUP') {
                            $update_pickup[] = $tracking_id;
                        }
                        if ($dt['status'] == 'DELIVERED') {
                            $update_delivered[] = $tracking_id;
                        }
                    }
                }
            }
            if (sizeof($update_pickup) > 0) {
                \DB::table('trackings')->whereIn('id', $update_pickup)
                    ->update([
                        'payment_status' => env('TRACK_PICKUP')
                    ]);
            }
            if (sizeof($update_delivered) > 0) {
                \DB::table('trackings')->whereIn('id', $update_delivered)
                    ->update([
                        'payment_status' => env('TRACK_DELIVERED')
                    ]);
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $status = 'error';
            $message = 'Xảy ra lỗi. Hãy thử lại.';
            logfile($message . ' - ' . $e->getMessage());
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
    }

    private function addTracking($data, $token)
    {
        $url = $this->url . "v1/shipping/trackers-batch";
        $accessToken = $token;
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
            "Content-Type: application/json"
        ));
        $result = curl_exec($curl);
        if (empty($result)) {
            $json = false;
            logfile("--[Paypal] [Error] Add Tracking Error: No response.");
        } else {
            $json = json_decode($result);
        }
        // Submit the POST request

        // Close cURL session handle
        curl_close($curl);
        return $json;
    }

    private function getPaymentStatus($transection_id, $access_token)
    {
        $link = $this->url;
        $url = $link . "v2/payments/captures/" . $transection_id;
        $accessToken = $access_token;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
            'Content-Type: application/json'
        ));
        $response = curl_exec($curl);
        if (empty($response)) {
            $json = false;
            logfile("--[Paypal] [Error] Get Payment Status: No response.");
        } else {
            $json = json_decode($response);
        }
        curl_close($curl);
        return $json;
    }

    private function updateTracking($path, $data, $access_token)
    {
        $url = $this->url . "v1/shipping/trackers/" . $path;
        $accessToken = $access_token;
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
            "Content-Type: application/json"
        ));
        $response = curl_exec($curl);
        if (empty($response)) {
            $json = true;

        } else {
            $json = false;
            logfile("--[Paypal] [Error] Update Tracking: No response.");
            print_r(json_decode($response, true));
        }
        curl_close($curl);
        return $json;
    }

    public function updatePaypalId()
    {

        $lists = \DB::table('woo_orders as wod')
            ->select('wod.id','wod.woo_info_id')
            ->where('wod.payment_method','Paypal')
            ->where('wod.paypal_id',0)
            ->get()->toArray();

        if (sizeof($lists) > 0)
        {
            $paypals = \DB::table('paypals')
                ->select('store_id','id')
                ->where('status',1)
                ->get()->toArray();
            $lst_paypal = array();
            foreach ($paypals as $paypal)
            {
                $lst_paypal[$paypal->store_id] = $paypal->id;
            }
            $db = array();
            foreach ($lists as $list)
            {
                if (array_key_exists($list->woo_info_id, $lst_paypal)){
                    $db[$lst_paypal[$list->woo_info_id]][] = $list->id;
                }
            }

            if (sizeof($db) >0)
            {
                foreach ($db as $paypal_id => $update_data)
                {
                    \DB::table('woo_orders')->whereIn('id',$update_data)->update([
                        'paypal_id' => $paypal_id
                    ]);
                    echo "Update thanh cong paypal_id : ".$paypal_id." co ".sizeof($update_data)." order <br>";
                }
            }
        } else {
            echo "Đã hết order có thể cập nhật paypal id <br>";
        }
    }

    public function getInfoTrackingUpPaypal()
    {
        $lst_status = [
            env('TRACK_INTRANSIT'),
            env('TRACK_PICKUP'),
            env('TRACK_DELIVERED')
        ];
        $return = false;
        // lấy danh sách tracking mới cần up lên paypal
        $lists = \DB::table('trackings as t')
            ->leftjoin('woo_orders', 't.order_id', '=', 'woo_orders.number')
            ->leftjoin('paypals', 'woo_orders.paypal_id', '=', 'paypals.id')
            ->select(
                't.id as tracking_id', 't.tracking_number', 't.order_id', 't.status', 't.shipping_method',
                'woo_orders.transaction_id',
                'paypals.id as paypal_id', 'paypals.email as paypal_email', 'paypals.client_id', 'paypals.client_secret'
            )
            ->where('woo_orders.paypal_id', '!=', 0)
            ->where('t.payment_up_tracking',env('PAYPAL_STATUS_NEW'))
            ->whereIn('t.status',$lst_status)
            ->limit(env('PAYPAL_LIMIT_UP_TRACKING'))
            ->get()->toArray();
        if (sizeof($lists) > 0)
        {
            // lấy toàn bộ danh sách đã thay đổi tên carrier name trước khi up tracking
            $track_17_carriers = \DB::table('17track_carriers as 17t')
                ->leftjoin('paypal_carriers', '17t.paypal_carrier_id', '=', 'paypal_carriers.id')
                ->select(
                    '17t.name as track_name',
                    'paypal_carriers.enumerated_value')
                ->get()->toArray();
            $carriers = array();
            if (sizeof($track_17_carriers) > 0)
            {
                $duplicate = array();
                foreach ($track_17_carriers as $item)
                {
                    $carriers[$item->track_name] = $item->enumerated_value;
                }
                $paypal = array();
                $paypal_carrier_not_choose = array();
                foreach($lists as $list)
                {
                    if (in_array($list->transaction_id, $duplicate))
                    {
                        continue;
                    } else {
                        $duplicate[] = $list->transaction_id;
                    }
                    if (array_key_exists($list->shipping_method, $carriers))
                    {
                        $paypal[$list->paypal_id]['info'] = [
                            'paypal_email' => $list->paypal_email,
                            'client_id' => $list->client_id,
                            'client_secret' => $list->client_secret
                        ];
                        $paypal[$list->paypal_id]['data']['trackers'][] = [
                            'transaction_id' => $list->transaction_id,
                            'tracking_number' => $list->tracking_number,
                            'status' => 'SHIPPED',
                            "carrier" => $carriers[$list->shipping_method]
                        ];
                        $paypal[$list->paypal_id]['list_tracking_id'][] = $list->tracking_id;
                    } else {
                        $paypal_carrier_not_choose[] = $list->tracking_id;
                    }
                }

                // nếu tồn tại tracking chưa chọn nhà cung cấp
                if (sizeof($paypal_carrier_not_choose) > 0)
                {
                    \DB::table('trackings')->whereIn('id',$paypal_carrier_not_choose)->update([
                        'payment_status' => env('PAYPAL_CARRIER_NOT_CHOOSE'),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                }

                if (sizeof($paypal) > 0)
                {
                    $paypal_success = array();
                    $paypal_error = array();
                    foreach($paypal as $paypal_id => $item)
                    {
                        $json = false;
                        try {
                            $client_id = $item['info']['client_id'];
                            $client_secret = $item['info']['client_secret'];
                            //Connect toi paypal
                            $json_data = $this->connect($client_id, $client_secret);
                            $access_token = $json_data->access_token;
                            $data = $item['data'];
                            $new_data = json_encode($data);
                            logfile_system('--- Đang cập nhật tracking number của '.sizeof($data['trackers']).' transection');
                            $json = $this->addTracking($new_data, $access_token);
                        } catch (\Exception $e) {
                            logfile_system('--- [Error] Không kết nối được với paypal API với lỗi: '.$e->getMessage());
                        }
                        if ($json) {
                            // neu paypal trả về trạng thái thành công
                            if(isset($json->tracker_identifiers)){
                                foreach($json->tracker_identifiers as $result_paypal)
                                {
                                    $paypal_success[] = $result_paypal->tracking_number;
                                }
                            }

                            // nếu có lỗi trả về từ paypal
                            if(sizeof($json->errors) > 0)
                            {
                                foreach ($json->errors as $type)
                                {
                                    foreach ($type as $case)
                                    {
                                        foreach($case->details as $result_paypal)
                                        {
                                            $paypal_error[] = $result_paypal->value; // transaction id
                                        }
                                    }
                                }
                            }
                        } else {
                            logfile_system('--- Cập nhật tracking number thất bại');
                            \DB::table('trackings')->whereIn('id', $item['list_tracking_id'])->update([
                                'payment_status' => env('PAYPAL_CARRIER_ERROR'),
                                'payment_up_tracking' => 2,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                        }
                    }
                    if (sizeof($paypal_success) > 0)
                    {
                        logfile_system('--- Cập nhật '.sizeof($paypal_success).' tracking number lên paypal thành công.');
                        \DB::table('trackings')->whereIn('tracking_number', $paypal_success)->update([
                            'payment_status' => env('PAYPAL_STATUS_SUCCESS'),
                            'payment_up_tracking' => 2, // Paypal success status
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                    }
                    if (sizeof($paypal_error) > 0)
                    {
                        logfile_system('--- Cập nhật '.sizeof($paypal_error).' tracking number thất bại');
                        $list_orders = \DB::table('woo_orders')->whereIn('transaction_id',$paypal_error)->pluck('number')->toArray();
                        if (sizeof($list_orders) > 0)
                        {
                            \DB::table('trackings')->whereIn('order_id', $list_orders)->update([
                                'payment_status' => env('PAYPAL_CARRIER_ERROR'),
                                'payment_up_tracking' => 3, // paypal error status
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                        }
                    }
                } else {
                    $return = true;
                    logfile_system('-- Chưa kết nối tới paypal hoặc chưa tồn tại order mua qua paypal');
                }
            } else {
                $return = true;
                logfile_system('-- Chưa chọn nhà cung cấp Carriers. Bạn cần chọn nhà cung cấp trước.');
            }
        } else {
            logfile_system('-- Đã hết tracking để up lên paypal. Chuyển sang công việc khác.');
            $return = true;
        }
        return $return;
    }

    public function test()
    {
        $client_id = '';
        $client_secret = '';

        $json_data = $this->connect($client_id, $client_secret);

        $data = array(
            'trackers' => array(
                array(
                    "transaction_id" => '1WY22840YW683581B',
                    "tracking_number" => 'LS035149979CN',
                    "status" => "SHIPPED",
                    "carrier" => "USPS"
                ),
                array(
                    "transaction_id" => '07776782R2315113C',
                    "tracking_number" => 'LS035149978CN',
                    "status" => "SHIPPED",
                    "carrier" => "USPS"
                )
            )
        );

        $update_data = array(
            "transaction_id" => "07776782R2315113C",
            'tracking_number' => 'LS035149982CN',
            "status" => "SHIPPED",
//            "status" => "CANCELLED",
            "carrier" => "USPS"
//            "carrier" => "AUSTRALIA_POST"
        );
        echo "<pre>";
        $data = json_encode($data);
        $update_data = json_encode($update_data);
        $access_token = $json_data->access_token;
        echo $access_token . "\n";

//        $json = $this->getPaymentStatus('07776782R2315113C',$access_token);

//        $json = $this->addTracking($data, $access_token);

        $path = '07776782R2315113C-LS035149982CN';
        $json = $this->updateTracking($path, $update_data, $access_token);

        var_dump($json);
    }
}
