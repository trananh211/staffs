<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Components\GoogleClient;
use Google_Service_Drive;
use DB;
use \Cache;
use File;
use Excel;

class GoogleController extends Controller
{
    /*GOOGLE API*/
    public function test()
    {
//        echo "<pre>";
//        $this->getFolderID($path);
//        $this->createDir('Public 45*45cm');
//        $this->deleteDir('Public');
//        $this->renameDir('hihi','Hehe');
//        echo $this->upFile(public_path(env('DIR_DONE').'S247-USA-3156-PID-19.jpg'),'1is5OXHePxYfjym8b0ackAqO0db4ItYpm');
    }

    public function createDir($name, $path = null)
    {
        $name = trim($name);
        $return = false;
        $recursive = false; // Get subdirectories also?
        if (Storage::cloud()->makeDirectory($path . '/' . $name)) {
            $dir = collect(Storage::cloud()->listContents($path, $recursive))
                ->where('type', '=', 'dir')
                ->where('filename', '=', $name)
                ->sortBy('timestamp')
                ->last();
            $return = $dir['path'];
        }
        return $return;
    }

    public function deleteDir($name, $path = null)
    {
        $return = false;
        $name = trim($name);
        $recursive = false; // Get subdirectories also?
        $check_before = collect(Storage::cloud()->listContents($path, $recursive))
            ->where('type', '=', 'dir')
            ->where('filename', '=', $name)
            ->first();
        if ($check_before) {
            if (Storage::cloud()->deleteDirectory($check_before['path'])) {
                $return = true;
            }
        }
        return $return;
    }

    public function renameDir($new_name, $old_name, $path = null)
    {
        $return = false;
        $new_name = trim($new_name);
        $old_name = trim($old_name);
        $recursive = false; // Get subdirectories also?
        $check_before = collect(Storage::cloud()->listContents($path, $recursive))
            ->where('type', '=', 'dir')
            ->where('filename', '=', $old_name)
            ->first();
        if ($check_before) {
            if (Storage::cloud()->move($check_before['path'], $new_name)) {
                $return = true;
            }
        }
        return $return;
    }

    public function upFile($path_info, $path = null)
    {
        $return = false;
        if (\File::exists($path_info)) {
            $filename = pathinfo($path_info)['basename'];
            $contents = File::get($path_info);
            if (Storage::cloud()->put($path . '/' . $filename, $contents)) {
                $recursive = false; // Get subdirectories also?
                $file = collect(Storage::cloud()->listContents($path, $recursive))
                    ->where('type', '=', 'file')
                    ->where('filename', '=', pathinfo($filename, PATHINFO_FILENAME))
                    ->where('extension', '=', pathinfo($filename, PATHINFO_EXTENSION))
                    ->sortBy('timestamp')
                    ->last();
                $return = $file['path'];
            }
        }
        return $return;
    }

    public function deleteFile($filename, $path, $parent_path = null)
    {
        $return = false;
        $name = trim($filename);
        $recursive = false; // Get subdirectories also?
        $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
            ->where('type', '=', 'file')
            ->where('name', '=', $filename)
            ->where('path', '=', $path)
            ->first();
        if ($check_before) {
            if (Storage::cloud()->delete($check_before['path'])) {
                $return = true;
            }
        }
        return $return;
    }

    /*END GOOGLE API*/


    /*Fulfillment*/

    public function fulfillment()
    {
        logfile('================= Fulfillment ==================');
        $paths = array(
            env('DIR_EXCEL_EXPORT')
        );
        foreach ($paths as $path) {
            if (!File::exists(public_path($path))) {
                File::makeDirectory(public_path($path), $mode = 0777, true, true);
            }
        }
        $lists = \DB::table('workings')
            ->join('woo_orders as wd', 'workings.woo_order_id', '=', 'wd.id')
            ->join('woo_products as wpd', 'workings.product_id', '=', 'wpd.product_id')
            ->select(
                'workings.id as working_id',
                'wd.id as woo_order_id', 'wd.order_status', 'wd.product_name', 'wd.number', 'wd.fullname', 'wd.address',
                'wd.city', 'wd.phone', 'wd.postcode', 'wd.country', 'wd.state', 'wd.status as fulfill_status',
                'wd.quantity', 'wd.customer_note', 'wd.email', 'wd.fullname',
                'wpd.name as product_origin_name', 'wpd.product_id'
            )
            ->where([
                ['workings.status', '=', env('STATUS_WORKING_DONE')]
            ])
            ->orderBy('workings.woo_order_id', 'ASC')
            ->get()
            ->toArray();
        if (sizeof($lists) > 0) {
            $check_again = array();
            $data = array();
            $ar_product = array();
            /*kiểm tra lại thư mục google driver đã tạo trước đó chưa*/
            $ar_google_driver = array();
            $ar_file_fulfill = array();
            foreach ($lists as $list) {
                /*Nếu khách chưa trả tiền. Kiểm tra lại với shop*/
                if ($list->order_status == 'pending') {
                    if ($list->fulfill_status == env('STATUS_NOTFULFILL')) continue;
                    if (in_array($list->woo_order_id, $check_again)) continue;
                    $check_again[] = $list->woo_order_id;
                    continue;
                } else {
                    /*check xem đã tạo folder name trên google driver hay chưa*/
                    $ar_google_driver[$list->product_origin_name] = $list->product_id;
                    /*Lấy data để lưu vào file excel fulfillment*/
                    $ar_product[$list->product_origin_name][] = [
                        'Order Number' => $list->number,
                        'Quantity' => $list->quantity,
                        'Customer Note' => $list->customer_note,
                        'Full Name' => $list->fullname,
                        'Address' => $list->address,
                        'City' => $list->city,
                        'State Code' => $list->state,
                        'Country Code' => $list->country,
                        'Postcode' => $list->postcode,
                        'Phone' => $list->phone,
                        'Email' => $list->email
                    ];
                    /*Lấy data để cập nhật trạng thái file hàng fulfilment thành công và bảng workings*/
                    $ar_file_fulfill[$list->product_origin_name][] = $list->working_id;
                }
            }
            /*Tạo thư mục trên google driver*/
            $check_google_driver = \DB::table('gg_folders')->select('name','dir')
                ->where('level','1')
                ->whereIn('product_id',$ar_google_driver)
                ->get();
            if (sizeof($check_google_driver) > 0)
            {
                foreach($check_google_driver as $item)
                {
                    if (array_key_exists($item->name, $ar_google_driver)) {
                        unset($ar_google_driver[$item->name]);
                    }
                }
            }
            if (sizeof($ar_google_driver) > 0)
            {
                $db_google_folder = array();
                foreach( $ar_google_driver as $product_name => $product_id) {
                    $path = $this->createDir(trim($product_name),env('GOOGLE_DRIVER_FOLDER_PUBLIC'));
                    if ($path) {
                        $db_google_folder[] = [
                            'name' => $product_name,
                            'path' => $path,
                            'parent_path' => env('GOOGLE_DRIVER_FOLDER_PUBLIC'),
                            'dir' => '/'.$product_name."/",
                            'product_id' => $product_id,
                            'level' => 1,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                    }
                }
                if ( sizeof($db_google_folder))
                {
                    \DB::table('gg_folders')->insert($db_google_folder);
                }
            }
            /*End tạo thư mục trên google driver*/
            $ud_working_move = array();
            foreach ($ar_product as $product_name => $dt) {
                $name = date("Y-m-d-His").'-'.$product_name ;
                $check = Excel::create($name, function ($excel) use ($dt) {
                    $excel->sheet('Sheet 1', function ($sheet) use ($dt) {
                        $sheet->fromArray($dt);
                    });
                })->store('csv', public_path(env('DIR_EXCEL_EXPORT')), true);
                if ( $check)
                {
                    $check_up = $this->upFile($check['full'],env('GOOGLE_DRIVER_FOLDER_PUBLIC'));
                    if ($check_up)
                    {
                        $ud_working_move = array_merge($ud_working_move, $ar_file_fulfill[$product_name]);
                        logfile('Fulfillment file excel thành công đơn hàng :'. $product_name.' số lượng: '.sizeof($dt));
                    }
                    \File::delete($check['full']);

                }
            }
            /*Nếu export file excel thành công. Tiến hành cập nhật file workings và move lên google driver*/
            if (sizeof($ud_working_move) > 0)
            {
                \DB::table('workings')->whereIn('id',$ud_working_move)
                    ->update([
                        'status' => env('STATUS_WORKING_MOVE'),
                        'updated_at' => date("Y-m-d H:i:s")
                        ]);
            }

            /*Nếu phát hiện ra có đơn hàng chưa trả tiền. Kiểm tra lại và fulfill vào ngày hôm sau*/
            if (sizeof($check_again) > 0) {
                \DB::table('woo_orders')->whereIn('id', $check_again)->update(['status' => env('STATUS_NOTFULFILL')]);
            }
        } else {
            logfile('Đã hết đơn hàng để fulfill');
        }

    }

    public function exportExcel($name, $data)
    {
        echo 'aaa';
        Excel::create($name, function ($excel) use ($data) {
            $excel->sheet('Sheet 1', function ($sheet) use ($data) {
                $sheet->fromArray($data);
            });
        })->export('xls');
//            ->store('csv', public_path('excel/exports'))

        echo 'bbb';
//        return $return;
    }
    /*End Fulfillment*/
}
