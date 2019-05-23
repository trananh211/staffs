<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use \Cache;
use File;
use Excel;
use Storage;

class Tracking extends Model
{
    public $timestamps = true;
    protected $table = 'trackings';

    public function getFileTracking()
    {
        $scan = scanFolder(env('GOOGLE_TRACKING_CHECK'));
        logfile("[Tracking Number Information]");
        if (sizeof($scan) > 0) {
            $check = array();
            /*Chỉ lấy file csv đầu tiên*/
            foreach ($scan as $file) {
                if ($file['extension'] != 'csv') {
                    /*Xóa file đi*/
                    if (deleteFile($file['name'], $file['path'], $file['dirname'])) {
                        logfile('Hệ thống xóa file rác: ' . $file['name']);
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
                    $path = public_path('storage/excel/read/' . $check['name']);
                    $dt = Excel::load($path)->get()->toArray();
                    //Gửi data sang hàm lọc tracking
                    if (sizeof($dt) > 0) {
                        $this->filterTracking($dt);
                    }
                    //Move file sau khi đã đọc xong
                    if (deleteFile($check['name'], $check['path'], $check['dirname'])) {
                        upFile($path, env('GOOGLE_TRACKING_DONE'));
                        \File::delete($path);
                        logfile('[Readed] Xóa file sau khi đã sử dụng xong');
                    }
                } else {
                    logfile('Không tải được file ' . $check['name'] . ' về server');
                }
            } else {
                logfile('Supplier chưa trả thêm file Tracking nào.');
            }
        } else {
            logfile('Không có file nào để kiểm tra.');
        }
    }

    /*Hàm Lọc dữ liệu Tracking*/
    private function filterTracking($data)
    {
        $db = array();
        $ar_working_id = array();
        foreach ($data as $value)
        {
            $tmp = explode('-',$value['orderid']);
            $working_id = $tmp[sizeof($tmp)-1];

            $ar_working_id[] = $working_id;
            $db[$working_id] = [
                'tracking_number' => $value['tracking'],
                'working_id' => $working_id,
                'order_id' => $value['orderid'],
                'status' => env('TRACK_NEW'),
                'is_check' => 0,
                'time_upload' => date("Y-m-d H:i:s"),
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ];
        }
        if (sizeof($ar_working_id) > 0)
        {
            //Kiểm tra tồn tại của tracking
            $trackings = \DB::table('trackings')
                ->whereIn('working_id',$ar_working_id)
                ->pluck('tracking_number','working_id')
                ->toArray();
            // Lấy danh sách những tracking đã hoạt động để bỏ qua tạo mới
            $trackings_active = \DB::table('trackings')
                ->whereIn('working_id',$ar_working_id)
                ->where('status','>',env('TRACK_NOTFOUND'))
                ->pluck('tracking_number','working_id')
                ->toArray();
            //lấy woo_order_id từ workings table
            $workings = \DB::table('workings')
                ->whereIn('id',$ar_working_id)
                ->pluck('woo_order_id','id')
                ->toArray();
            // Nếu chưa có file nào từng tồn tại ở DB
            if (sizeof($trackings) == 0) {
                logfile('--- Đây là tracking mới. Chuẩn bị tạo mới fulfillment');
                foreach ($db as $working_id => $dt){
                    $db[$working_id]['woo_order_id'] = $workings[$working_id];
                }
                \DB::table('trackings')->insert($db);
                logfile('--- Tạo thành công '.sizeOf($db).'tracking mới.');
            } else {
                $del_trackings = array();
                foreach ($db as $working_id => $value)
                {
                    // Nếu phát hiện ra có tracking đã active thì bỏ qua luôn
                    if ( array_key_exists($working_id, $trackings_active)) {
                        logfile('---- Tracking '.$trackings_active[$working_id].' : đã actived không thể thêm tự động.');
                        unset($db[$working_id]);
                        continue;
                    }
                    if (array_key_exists($working_id, $trackings)) {
                        if ($value['tracking_number'] == $trackings[$working_id]) {
                            logfile('---- Tracking đã tồn tại. bỏ qua : '.$value['tracking_number']);
                            unset($db[$working_id]);
                            continue;
                        } else {
                            $del_trackings[] = $working_id;
                        }
                    }
                    $db[$working_id]['woo_order_id'] = $workings[$working_id];
                }
                /*Nếu supplier gửi lên file tracking mới*/
                if (sizeof($del_trackings) > 0)
                {
                    logfile('--- Supplier gửi lên '.sizeof($del_trackings).' file tracking mới');
                    \DB::table('trackings')->whereIn('working_id',$del_trackings)->delete();
                }
                // Nếu vẫn tồn tại tracking mới. Lưu vào database
                if (sizeof($db) > 0)
                {
                    \DB::table('trackings')->insert($db);
                    logfile('--- Tạo thành công '.sizeof($db).' tracking mới.');
                } else {
                    logfile('---[Trùng lặp] Supplier gửi lên file tracking đã cũ.');
                }
            }
        }
    }

    /*Hàm lấy info tracking*/
    public function getInfoTracking()
    {
        //Kiểm tra xem có file tracking nào đang không tồn tại hay không
        $lists = \DB::table('trackings')
            ->select('id','tracking_number','status')
            ->where('is_check', 0)
            ->orderBy('updated_at','DESC')
            ->limit(30)
            ->get();
        if (sizeof($lists) > 0)
        {
            $str_url = '';
            foreach ($lists as $list)
            {
                $str_url .= $list->tracking_number.',';
            }
            $str_url = rtrim($str_url,',');
            $url = env('TRACK_URL').$str_url;
            echo $url;
        } else {
            logfile('Đã hết file tracking. Cập nhật lại danh sách chưa xong');
            \DB::table('trackings')
                ->whereNotIn('status',array(env('TRACK_DELIVERED', env('TRACK_EXPIRED'))))
                ->update([
                    'is_check' => 0,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);

        }
    }
}
