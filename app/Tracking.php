<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use DB;
use \Cache;
use File;
use Excel;
use Storage;

class Tracking extends Model
{
    public $timestamps = true;
    protected $table = 'trackings';

    public function tracking()
    {
        $lists = \DB::table('woo_orders as wod')
            ->leftJoin('trackings', 'trackings.woo_order_id', '=', 'wod.id')
            ->leftJoin('workings', 'wod.id', '=', 'workings.woo_order_id')
            ->select(
                'wod.id as woo_order_id', 'wod.number', 'wod.product_name', 'wod.quantity', 'wod.updated_at', 'wod.status',
                'trackings.tracking_number', 'trackings.status as tracking_status', 'trackings.time_upload',
                'workings.id as working_id'
            )
            ->whereIn('wod.status', [env('STATUS_UPLOADED'), env('STATUS_WORKING_MOVE')])
            ->get();
        $data = infoShop();
        return view('/admin/tracking')->with(compact('lists', 'data'));
    }

    public function getFileTracking()
    {
        try {
            $scan = scanFolder(env('GOOGLE_TRACKING_CHECK'));
            if (sizeof($scan) > 0) {
                logfile("[Tracking Number Information]");
                $check = array();
                /*Chỉ lấy file csv đầu tiên*/
                foreach ($scan as $file) {
                    if ($file['extension'] != 'csv') {
                        /*Xóa file đi*/
                        $move = Storage::cloud()->move($file['path'], env('GOOGLE_TRACKING_DELETE') . '/' . $file['name']);
                        if ($move) {
                            logfile('--- Hệ thống xóa file rác: ' . $file['name']);
                        }
                        continue;
                    }
                    // Bắt đầu lấy file csv và nhảy ra khỏi vòng lặp
                    $check = $file;
                    break;
                }

                // Kiểm tra xem có file CSV nào hay không
                if (sizeof($check) > 0) {
                    $rawData = Storage::cloud()->get($check['path']);
                    $content = response($rawData);
                    /*Download file về local*/

                    if (Storage::disk('public')->put('excel/read/' . $check['name'], $rawData)) {
                        $path = storage_path('app/public/excel/read/' . $check['name']);
                        $dt = Excel::load($path)->get()->toArray();
                        //Gửi data sang hàm lọc tracking
                        if (sizeof($dt) > 0) {
                            $this->filterTracking($dt);
                        }
                        //Move file sau khi đã đọc xong
                        $move = Storage::cloud()->move($check['path'], env('GOOGLE_TRACKING_DONE') . '/' . $check['name']);
                        if ($move) {
                            \File::delete($path);
                            logfile('--- [Readed] Xóa file sau khi đã sử dụng xong');
                        }
                    } else {
                        logfile('--- Không tải được file ' . $check['name'] . ' về server');
                    }
                } else {
                    logfile('--- Supplier chưa trả thêm file Tracking nào.');
                }
            } else {
                logfile('[Tracking Number Information] Không có file nào để kiểm tra.');
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $save = "[Tracking Error] Xảy ra lỗi nội bộ: \n" . $e . "\n";
            logfile($save);
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
    }

    /*Hàm Lọc dữ liệu Tracking*/
    private function filterTracking($data)
    {
        $db = array();
        $ar_working_id = array();
        $ar_woo_order_id = array();
        foreach ($data as $value) {
            $tmp = explode('-', $value['orderid']);
            $woo_order_id = $tmp[sizeof($tmp) - 1];
            $ar_working_id[] = $woo_order_id;
            $ar_woo_order_id[] = $woo_order_id;
            $tracking_number = preg_replace('/\s+/', '', htmlentities(sanitizer(trim($value['tracking']))));
            if (trim($tracking_number) == '')
            {
                continue;
                logfile('-- [Warning] Tracking của Order: '.$value['order_id'].' rỗng. Bỏ qua!');
            }
            $db[$woo_order_id] = [
                'tracking_number' => $tracking_number,
                'woo_order_id' => $woo_order_id,
                'order_id' => $value['orderid'],
                'status' => env('TRACK_NEW'),
                'is_check' => 0,
                'time_upload' => date("Y-m-d H:i:s"),
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ];
        }
        if (sizeof($ar_woo_order_id) > 0) {
            //Kiểm tra tồn tại của tracking
            $trackings = \DB::table('trackings')
                ->whereIn('woo_order_id', $ar_woo_order_id)
                ->pluck('tracking_number', 'woo_order_id')
                ->toArray();
            // Lấy danh sách những tracking đã hoạt động để bỏ qua tạo mới
            $trackings_active = \DB::table('trackings')
                ->whereIn('woo_order_id', $ar_woo_order_id)
                ->where('status', '>', env('TRACK_NOTFOUND'))
                ->pluck('tracking_number', 'woo_order_id')
                ->toArray();
            // Nếu chưa có file nào từng tồn tại ở DB
            if (sizeof($trackings) == 0) {
                \DB::table('trackings')->insert($db);
                logfile('--- Tạo thành công ' . sizeOf($db) . ' tracking mới.');
            } else {
                $del_trackings = array();
                foreach ($db as $woo_order_id => $value) {
                    // Nếu phát hiện ra có tracking đã active thì bỏ qua luôn
                    if (array_key_exists($woo_order_id, $trackings_active)) {
                        logfile('---- Tracking của ' . $value['order_id'] . ' : '
                            . $trackings_active[$woo_order_id] . ' : đã actived không thể thêm tự động.');
                        unset($db[$woo_order_id]);
                        continue;
                    }
                    if (array_key_exists($woo_order_id, $trackings)) {
                        if ($value['tracking_number'] == $trackings[$woo_order_id]) {
                            logfile('---- Tracking của ' . $value['order_id'] . ' đã tồn tại. bỏ qua : '
                                . $value['tracking_number']);
                            unset($db[$woo_order_id]);
                            continue;
                        } else {
                            $del_trackings[] = $woo_order_id;
                        }
                    }
                }
                /*Nếu supplier gửi lên file tracking mới. Xóa tracking cũ đi để thêm mới*/
                if (sizeof($del_trackings) > 0) {
                    logfile('--- Supplier gửi lên ' . sizeof($del_trackings) . ' file tracking mới');
                    \DB::table('trackings')->whereIn('woo_order_id', $del_trackings)->delete();
                }
                // Nếu vẫn tồn tại tracking mới. Lưu vào database
                if (sizeof($db) > 0) {
                    \DB::table('trackings')->insert($db);
                    logfile('--- Tạo thành công ' . sizeof($db) . ' tracking mới.');
                } else {
                    logfile('--- [Trùng lặp] Supplier gửi lên file tracking đã cũ.');
                }
            }
        }
    }

    /*Hàm lấy info tracking*/
    public function getInfoTracking()
    {
        //Kiểm tra xem có file tracking nào đang không tồn tại hay không
        $lists = \DB::table('trackings')
            ->select('id', 'tracking_number', 'status', 'order_id')
            ->where('is_check', 0)
            ->where('status', '!=', env('TRACK_DELIVERED'))
            ->orderBy('updated_at', 'DESC')
            ->limit(30)
            ->get();
        if (sizeof($lists) > 0) {
            logfile("[Tracking] Kiểm tra tracking của " . sizeof($lists) . " đơn hàng");
            $str_url = '';
            $ar_data = array();
            $checked = [];
            $ar_update = array();
            foreach ($lists as $list) {
                $checked[] = $list->id;
                //nhiều order chung 1 tracking number vẫn phải được cập nhật
                $ar_data[$list->tracking_number] = $list;
                if ($list->tracking_number != '') {
                    $str_url .= $list->tracking_number . ',';
                }
            }
            $url = env('TRACK_URL') . rtrim($str_url, ',');
            //Gui request den API App
            $client = new Client(); //GuzzleHttp\Client
            $res = $client->request('GET', $url);
            $json_data = json_decode($res->getBody(), true);
            foreach ($json_data as $info_track) {
                $tracking_number = trim($info_track['title']);
                if (!array_key_exists($tracking_number, $ar_data)) {
                    continue;
                }
                $result = $this->checkTrackingResult($info_track['value'], $ar_data[$tracking_number]->status);
                if ($result) {
                    $ar_update[$result][] = $tracking_number;
                    logfile('--- Cập nhật đơn hàng : ' . $ar_data[$tracking_number]->order_id .
                        ' có mã tracking : ' . $tracking_number . ' thay đổi thành ' . $info_track['value']);
                } else {
                    logfile('--- Đơn hàng : ' . $ar_data[$tracking_number]->order_id .
                        ' có mã tracking : ' . $tracking_number . ' chưa thay đổi trạng thái ' . $info_track['value']);
                }
            }
            if (sizeof($ar_update) > 0) {
                //Cap nhật trạng thái mới
                foreach ($ar_update as $tracking_status => $list_tracking) {
                    \DB::table('trackings')->whereIn('tracking_number', $list_tracking)
                        ->update([
                            'status' => $tracking_status,
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                    if ($tracking_status == env('TRACK_DELIVERED')) {
                        \DB::table('woo_orders')->whereIn('id', function ($query) use ($list_tracking) {
                            $query->select('woo_order_id')
                                ->from('trackings')
                                ->whereIn('tracking_number', $list_tracking);
                        })->update([
                            'status' => env('STATUS_FINISH'),
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                    }
                }
            }
            //Cập nhật trạng thái đã checking
            if (sizeof($checked) > 0) {
                \DB::table('trackings')->whereIn('id', $checked)
                    ->update([
                        'is_check' => 1,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
            }
        } else {
            logfile('[Tracking] Đã hết file tracking. Cập nhật lại danh sách order chưa DELIVERED');
            \DB::table('trackings')
                ->whereNotIn('status', array(env('TRACK_DELIVERED', env('TRACK_EXPIRED'))))
                ->update([
                    'is_check' => 0,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);

        }
    }

    private function checkTrackingResult($text, $value_old)
    {
        $text = strtolower($text);
        $return = false;
        $val_track = 0;
        if (strpos($text, 'not found') !== false) {
            $val_track = env('TRACK_NOTFOUND');
        } else if (strpos($text, 'in transit') !== false) {
            $val_track = env('TRACK_INTRANSIT');
        } else if (strpos($text, 'pick up') !== false) {
            $val_track = env('TRACK_PICKUP');
        } else if (strpos($text, 'undelivered') !== false) {
            $val_track = env('TRACK_UNDELIVERED');
        } else if (strpos($text, 'delivered') !== false) {
            $val_track = env('TRACK_DELIVERED');
        } else if (strpos($text, 'alert') !== false) {
            $val_track = env('TRACK_ALERT');
        } else if (strpos($text, 'expired') !== false) {
            $val_track = env('TRACK_EXPIRED');
        }
        if ($val_track > $value_old) {
            $return = $val_track;
        }
        return $return;
    }

    /*Paypal tracking*/
    public function postTrackingNumber($request)
    {
//        echo "<pre>";
        $rq = $request->all();
        $paypal_file = $rq['paypal_file'];
        $tracking_file = $rq['tracking_file'];
        echo storage_path('paypal')."\n";
        makeFolder(storage_path('paypal'));
        $file_paypal = $paypal_file->move(storage_path('paypal'),$paypal_file->getClientOriginalName());
        if ($file_paypal)
        {
            $file_tracking = $tracking_file->move(storage_path('paypal'),$tracking_file->getClientOriginalName());
            if ($file_tracking)
            {
                $path_file_paypal = storage_path('paypal/'.$paypal_file->getClientOriginalName());
                $path_file_tracking = storage_path('paypal/'.$tracking_file->getClientOriginalName());
                $data_paypal = readFileExcel($path_file_paypal);
                $data_tracking = readFileExcel($path_file_tracking);
                $lst_tracking_number = array();
                foreach ($data_tracking as $val_tracking)
                {
                    $order = trim($val_tracking['order']);
                    if (array_key_exists($order, $lst_tracking_number))
                    {
                        //neu chua ton tai
                        if (strpos($lst_tracking_number[$order], $val_tracking['tracking_number']) !== false)
                        {
                            continue;
                        } else {
                            if ($lst_tracking_number[$order] == '')
                            {
                                $lst_tracking_number[$order] = $val_tracking['tracking_number'];
                            } else {
                                $lst_tracking_number[$order] .= ','.$val_tracking['tracking_number'];
                            }
                        }
                    } else {
                        $lst_tracking_number[$order] = $val_tracking['tracking_number'];
                    }
                }

                foreach ($data_paypal as $k => $paypal)
                {
                    $order = str_replace("ZAC-ZAC-","ZAC-",$paypal['invoice_number']);
                    if (array_key_exists($order, $lst_tracking_number))
                    {
                        $data_paypal[$k]['tracking'] = $lst_tracking_number[$order];
                    } else {
                        $data_paypal[$k]['tracking'] = '';
                    }
                }
                echo "<pre>";
                print_r($data_paypal);
                $check = createFileExcel('tracking_full',$data_paypal, storage_path('paypal') ,'paypal');
            }

        }

    }


    /*End Paypal tracking*/
}
