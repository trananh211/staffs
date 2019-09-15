<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Paypal extends Model
{

    public $timestamps = true;
    protected $table = 'paypals';

//    private $url = 'https://api.paypal.com/';
    private $url = 'https://api.sandbox.paypal.com/';

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

    public function connect($clientId, $secret)
    {
        $json = array();
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.sandbox.paypal.com/v1/oauth2/token");
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

    public function test()
    {
        $client_id = 'AXYckDa34gcMixJNiHHKAi9NOHniOAyg3fD8gN5ynfRDgRWLCCjaWt6rcOhLTnkrbX6jQeshnxg5lAD7';
        $client_secret = 'EFZDAjMrCS1qOD9bV6YoSPgFOux2srRwJ3WwOOzBz3RoRSFlmCOLzRwb7lKandMADBgq3trU6gSLXBrj';

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
