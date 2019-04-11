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
//        $parent_path = env("GOOGLE_DRIVER_FOLDER_PUBLIC");
//        $path = renameDir('Personalised Tottenham Hotspurs FC - Pillow Case', 'PC021 Personalised Tottenham Hotspurs FC - Pillow Case',$parent_path);
//        $path = renameDir('PC021 Personalised Tottenham Hotspurs FC - Pillow Case', 'Personalised Tottenham Hotspurs FC - Pillow Case' ,$parent_path);
//        echo $path."\n<br>";
//        echo $parent_path;
//        deleteDir('Public');
//        renameDir('hihi','Hehe');
//        echo upFile(public_path(env('DIR_DONE').'S247-USA-3156-PID-19.jpg'),'1is5OXHePxYfjym8b0ackAqO0db4ItYpm');
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
                'wd.quantity', 'wd.customer_note', 'wd.email', 'wd.fullname', 'wd.sku',
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
//            $ar_google_driver = array();
            $ar_file_fulfill = array();
            foreach ($lists as $list) {
                /*Nếu khách chưa trả tiền. Kiểm tra lại với shop*/
                if (in_array($list->order_status, array('failed','canceled','pending'))) {
                    if ($list->fulfill_status == env('STATUS_NOTFULFILL')) continue;
                    if (in_array($list->woo_order_id, $check_again)) continue;
                    $check_again[] = $list->woo_order_id;
                    logfie('Đơn hàng '.$list->woo_order_id.' chưa thanh toán tiền');
                    continue;
                } else {
                    /*check xem đã tạo folder name trên google driver hay chưa*/
//                    $ar_google_driver[$list->product_origin_name] = $list->product_id;
                    /*Lấy data để lưu vào file excel fulfillment*/
                    $ar_product[$list->product_origin_name][] = [
                        'Order Number' => $list->number,
                        'SKU' => $list->sku,
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
                    logfile('Đang fulfill đơn '.$list->number.' vào excel');
                }
            }
            /*Tạo thư mục trên google driver*/
            /*$check_google_driver = \DB::table('gg_folders')->select('name', 'dir')
                ->where('level', '1')
                ->whereIn('product_id', $ar_google_driver)
                ->get();
            if (sizeof($check_google_driver) > 0) {
                foreach ($check_google_driver as $item) {
                    if (array_key_exists($item->name, $ar_google_driver)) {
                        unset($ar_google_driver[$item->name]);
                    }
                }
            }
            if (sizeof($ar_google_driver) > 0) {
                $db_google_folder = array();
                foreach ($ar_google_driver as $product_name => $product_id) {
                    $path = createDir(trim($product_name), env('GOOGLE_DRIVER_FOLDER_PUBLIC'));
                    if ($path) {
                        $db_google_folder[] = [
                            'name' => $product_name,
                            'path' => $path,
                            'parent_path' => env('GOOGLE_DRIVER_FOLDER_PUBLIC'),
                            'dir' => trim($product_name) . "/",
                            'product_id' => $product_id,
                            'level' => 1,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                    }
                }
                if (sizeof($db_google_folder)) {
                    \DB::table('gg_folders')->insert($db_google_folder);
                }
            }*/
            /*End tạo thư mục trên google driver*/
            $ud_working_move = array();
            foreach ($ar_product as $product_name => $dt) {
                $name = date("Y-m-d") . '-' . $product_name;
                $check = Excel::create($name, function ($excel) use ($dt) {
                    $excel->sheet('Sheet 1', function ($sheet) use ($dt) {
                        $sheet->fromArray($dt);
                    });
                })->store('csv', public_path(env('DIR_EXCEL_EXPORT')), true);
                if ($check) {
                    $check_up = upFile($check['full'], env('GOOGLE_DRIVER_FOLDER_PUBLIC'));
                    if ($check_up) {
                        $ud_working_move = array_merge($ud_working_move, $ar_file_fulfill[$product_name]);
                        logfile('Fulfillment file excel thành công đơn hàng :' . $product_name . ' số lượng: ' . sizeof($dt));
                    }
//                    \File::delete($check['full']);
                }
            }
            /*Nếu export file excel thành công. Tiến hành cập nhật file workings và move lên google driver*/
            if (sizeof($ud_working_move) > 0) {
                \DB::table('workings')->whereIn('id', $ud_working_move)
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

    public function uploadFileDriver()
    {
        $lists = \DB::table('workings')
            ->join('woo_orders as wod', 'workings.woo_order_id', '=', 'wod.id')
            ->join('working_files as file', 'workings.id', '=', 'file.working_id')
            ->join('woo_products as wpd', 'workings.product_id', '=', 'wpd.product_id')
            ->select(
                'workings.id as working_id', 'wod.product_id', 'wod.product_name',
                'wpd.name as product_name_origin', 'file.name as filename', 'file.path','file.id as working_file_id'
            )
            ->where('workings.status', env('STATUS_WORKING_MOVE'))
            ->where('file.is_mockup',0)
            ->get();
        if (sizeof($lists) > 0) {
            /*Phân loại file theo product*/
            $ar_product = array();
            $ar_file_info = array();
            $ar_product_id = array();
            $ar_google_level2 = array();
            foreach ($lists as $list) {
                $ar_product_id[$list->product_id] = $list->product_id;
                $ar_product[$list->product_id] = $list->product_name_origin;
                $ar_google_level2[$list->product_name] = $list->product_id;
                $ar_file_info[$list->product_name][] = $list;
            }
            /*Tìm trên google driver các thư mục con của product*/
            $lst_google_folder = \DB::table('gg_folders')
                ->where('level', 1)
                ->whereIn('product_id', $ar_product_id)
                ->pluck('path', 'product_id')
                ->toArray();

            $db_google_folder = array();
            foreach ($ar_product as $product_id => $product_name) {
                if (!array_key_exists($product_id, $lst_google_folder)) {
                    $path = createDir(trim($product_name), env('GOOGLE_DRIVER_FOLDER_PUBLIC'));
                    if ($path) {
                        $db_google_folder[] = [
                            'name' => $product_name,
                            'path' => $path,
                            'parent_path' => env('GOOGLE_DRIVER_FOLDER_PUBLIC'),
                            'dir' => trim($product_name) . "/",
                            'product_id' => $product_id,
                            'level' => 1,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                    }
                }
            }
            if (sizeof($db_google_folder) > 0) {
                \DB::table('gg_folders')->insert($db_google_folder);
                $lst_google_folder = \DB::table('gg_folders')
                    ->where('level', 1)
                    ->whereIn('product_id', $ar_product_id)
                    ->pluck('path', 'product_id')
                    ->toArray();
            }
            /*Lấy ra danh sách google folder level 2*/
            $lst_google_level2 = \DB::table('gg_folders')
                ->where('level',2)->whereIn('product_id',$ar_product_id)
                ->pluck('path','name')
                ->toArray();
            $db_google_level2 = array();
            foreach($ar_google_level2 as $product_name => $product_id)
            {
                if (!array_key_exists($product_name,$lst_google_level2))
                {
                    $path = createDir(trim($product_name), $lst_google_folder[$product_id]);
                    if ($path) {
                        $db_google_level2[] = [
                            'name' => $product_name,
                            'path' => $path,
                            'parent_path' => $lst_google_folder[$product_id],
                            'dir' => trim($product_name) . "/",
                            'product_id' => $product_id,
                            'level' => 2,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                    }
                }
            }
            if (sizeof($db_google_level2) > 0) {
                \DB::table('gg_folders')->insert($db_google_level2);
                $lst_google_level2 = \DB::table('gg_folders')
                    ->where('level',2)->whereIn('product_id',$ar_product_id)
                    ->pluck('path','name')
                    ->toArray();
            }

            $db_google_files = array();
            $ud_status_workings = array();
            /*Up file lên google Driver*/
            $i= 0;
            $alias = date('Ymd');
            foreach ($ar_file_info as $product_name => $files)
            {
                if ($i > 2) break;
                $i++;
                foreach($files as $file)
                {

                    $parent_path = $lst_google_level2[$product_name];
                    $dir_info = public_path($file->path.$file->filename);
//                    $tmp = [
//                        'parent_path' => $parent_path,
//                        'dir' => $dir_info,
//                        'product_id' => $file->product_id,
//
//                    ];
                    $new_name = $alias.'-'.$file->filename;
                    $path = upFile($dir_info, $parent_path , $new_name);
                    if ( $path)
                    {
                        logfile('Up thành công file '.$file->filename.' lên google Driver');
                        $db_google_files[] = [
                            'name' => $new_name,
                            'path' => $path,
                            'parent_path' => $parent_path,
                            'product_id' => $file->product_id,
                            'working_file_id' => $file->working_file_id,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $ud_status_workings[$file->working_id] = $file->working_id;
                    } else {
                        logfile('Up thất bại file '.$file->filename.' lên google Driver');
                    }
                }
            }

            if (sizeof($db_google_files) > 0)
            {
                \DB::table('gg_files')->insert($db_google_files);
                \DB::table('workings')->whereIn('id',$ud_status_workings)->update([
                    'status' => env('STATUS_UPLOADED'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                \DB::table('working_files')->whereIn('working_id',$ud_status_workings)->update([
                    'status' => env('STATUS_UPLOADED'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            }
        }
    }
    /*End Fulfillment*/
}
