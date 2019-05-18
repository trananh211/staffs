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
    public function fulFillByHand()
    {
        $data = infoShop();
        $this->fulfillment();
        return view('admin/woo/webhooks', compact('data'));
    }

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
                'wd.quantity', 'wd.customer_note', 'wd.email', 'wd.fullname', 'wd.sku', 'wd.sku_number',
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
            $ar_file_fulfill = array();
            $ar_order_fulfill = array();
            foreach ($lists as $list) {
                $list->product_origin_name = sanitizer($list->product_origin_name);
                /*Nếu khách chưa trả tiền. Kiểm tra lại với shop*/
                if (in_array($list->order_status, array('failed', 'cancelled', 'canceled', 'pending'))) {
                    if ($list->fulfill_status == env('STATUS_NOTFULFILL')) continue;
                    if (in_array($list->woo_order_id, $check_again)) continue;
                    $check_again[] = $list->woo_order_id;
                    logfile('Đơn hàng ' . $list->number . ' chưa thanh toán tiền');
                    continue;
                } else {
                    /*Lấy data để lưu vào file excel fulfillment*/
                    $ar_product[$list->product_origin_name][] = [
                        'Order Number' => $list->number,
                        'SKU' => $list->sku_number,
                        'Tracking' => '',
                        'SKU_2' => $list->sku,
                        'OrderId' => $list->number . '-' . $list->working_id,
                        'Product Name' => $list->product_origin_name,
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
                    $ar_order_fulfill[$list->product_origin_name][] = $list->woo_order_id;
                    logfile('Đang fulfill đơn ' . $list->number . ' vào excel');
                }
            }
            $ud_working_move = array();
            $ud_order_move = array();
            $new_gg_files = array();
            $files_fulfillment = \DB::table('gg_files')
                ->where('type', 2)
                ->whereDate('updated_at', date("Y-m-d"))
                ->pluck('id', 'name')
                ->toArray();
            $ar_del_gg_files = array();
            $check2 = false;
            foreach ($ar_product as $product_name => $dt) {
                $name = date("Y-m-d") . '-' . $product_name;
                $name2 = date('Y-m-d');
                $check = $this->makeExcel($name, $dt);
                $check2 = $this->makeExcel($name2, $dt);
                if ($check) {
                    $check_up = upFile($check['full'], env('GOOGLE_DRIVER_FOLDER_PUBLIC'));
                    if ($check_up) {
//                      Kiểm tra files đã từng up lên google driver hay chưa
                        if (array_key_exists($name, $files_fulfillment)) {
                            $ar_del_gg_files[] = $files_fulfillment[$name];
                        }
                        $new_gg_files[] = [
                            'name' => $name,
                            'path' => $check_up,
                            'parent_path' => env('GOOGLE_DRIVER_FOLDER_PUBLIC'),
                            'type' => 2,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $ud_working_move = array_merge($ud_working_move, $ar_file_fulfill[$product_name]);
                        $ud_order_move = array_merge($ud_order_move, $ar_order_fulfill[$product_name]);
                        logfile('Fulfillment file excel thành công đơn hàng :' . $product_name . ' số lượng: ' . sizeof($dt));
                    }
//                    \File::delete($check['full']);
                }
            }
//          Nếu tồn tại file đã up lên trước đó rồi. Xóa trên google driver và xóa ở dưới database trước
            if (sizeof($ar_del_gg_files) > 0) {
                $files_del_fulfillment = \DB::table('gg_files')
                    ->select('name','path','parent_path')
                    ->whereIn('id', $ar_del_gg_files)
                    ->get();
                foreach ( $files_del_fulfillment as $file) {
                    deleteFile($file->name.'.csv',$file->path,$file->parent_path);
                    logfile('-- Tồn tại file :'.$file->name.'.csv trước đó rồi. Đang xóa trên thư mục Drive Google');
                }
                $result = \DB::table('gg_files')->whereIn('id',$ar_del_gg_files)->delete();
                if ($result) {
                    if (sizeof($new_gg_files) > 0) {
                        \DB::table('gg_files')->insert($new_gg_files);
                    }
                }
            } else {
                if (sizeof($new_gg_files) > 0) {
                    \DB::table('gg_files')->insert($new_gg_files);
                }
            }
            if ($check2) {
                $check_up2 = upFile($check2['full'], env('GOOGLE_DRIVER_FOLDER_PUBLIC'));
//              Nếu tồn tại file tổng đã upload trước đó. Xóa trên google driver và cập nhật lại ở database
                if (array_key_exists($name2, $files_fulfillment)){
                    $id = $files_fulfillment[$name2];
                    $file = \DB::table('gg_files')
                        ->select('name','path','parent_path')
                        ->where('id', $id)
                        ->first();
                    deleteFile($file->name.'.csv',$file->path,$file->parent_path);
                    \DB::table('gg_files')->where('id',$id)->update([
                        'path' => $check_up2,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                } else {
                    \DB::table('gg_files')->insert([
                        'name' => $name2,
                        'path' => $check_up2,
                        'parent_path' => env('GOOGLE_DRIVER_FOLDER_PUBLIC'),
                        'type' => 2,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                }

//                \File::delete($check2['full']);
            }
            /*Nếu export file excel thành công. Tiến hành cập nhật file workings và move lên google driver*/
            if (sizeof($ud_working_move) > 0) {
                \DB::table('workings')->whereIn('id', $ud_working_move)
                    ->update([
                        'status' => env('STATUS_WORKING_MOVE'),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                \DB::table('woo_orders')->whereIn('id', $ud_order_move)
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

    private static function makeExcel($name, $data)
    {
        $path = public_path(env('DIR_EXCEL_EXPORT')) . '/' . $name . '.csv';
        if (File::exists($path)) {
            $dt = Excel::load($path)->get()->toArray();
            $rows = array_merge($dt, $data);
        } else {
            $rows = $data;
        }
        $check = Excel::create($name, function ($excel) use ($rows) {
            $excel->sheet('Sheet 1', function ($sheet) use ($rows) {
                $sheet->fromArray($rows);
            });
        })->store('csv', public_path(env('DIR_EXCEL_EXPORT')), true);
        return $check;
    }

    public function uploadFileDriver()
    {
        $lists = \DB::table('workings')
            ->join('woo_orders as wod', 'workings.woo_order_id', '=', 'wod.id')
            ->join('working_files as file', 'workings.id', '=', 'file.working_id')
            ->join('woo_products as wpd', 'workings.product_id', '=', 'wpd.product_id')
            ->select(
                'workings.id as working_id', 'wod.product_id', 'wod.product_name',
                'wpd.name as product_name_origin', 'file.name as filename', 'file.path', 'file.id as working_file_id'
            )
            ->where('workings.status', env('STATUS_WORKING_MOVE'))
            ->where('file.is_mockup', 0)
            ->get();
        if (sizeof($lists) > 0) {
            /*Phân loại file theo product*/
            $ar_product = array();
            $ar_file_info = array();
            $ar_product_id = array();
            $ar_google_level2 = array();
            foreach ($lists as $list) {
                $ar_product_id[$list->product_id] = $list->product_id;
                $ar_product[$list->product_id] = sanitizer($list->product_name_origin);
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
                ->where('level', 2)->whereIn('product_id', $ar_product_id)
                ->pluck('path', 'name')
                ->toArray();
            $db_google_level2 = array();
            foreach ($ar_google_level2 as $product_name => $product_id) {
                if (!array_key_exists($product_name, $lst_google_level2)) {
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
                    ->where('level', 2)->whereIn('product_id', $ar_product_id)
                    ->pluck('path', 'name')
                    ->toArray();
            }

            $db_google_files = array();
            $ud_status_workings = array();
            /*Up file lên google Driver*/
            $i = 0;
            $alias = date('Ymd');
            foreach ($ar_file_info as $product_name => $files) {
                if ($i > 2) break;
                $i++;
                foreach ($files as $file) {
                    $parent_path = $lst_google_level2[$product_name];
                    $dir_info = public_path($file->path . $file->filename);
                    $new_name = $alias . '-' . $file->filename;
                    $path = upFile($dir_info, $parent_path, $new_name);
                    if ($path) {
                        logfile('Up thành công file ' . $file->filename . ' lên google Driver');
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
                        logfile('Up thất bại file ' . $file->filename . ' lên google Driver');
                    }
                }
            }

            if (sizeof($db_google_files) > 0) {
                \DB::table('gg_files')->insert($db_google_files);
                \DB::table('workings')->whereIn('id', $ud_status_workings)->update([
                    'status' => env('STATUS_UPLOADED'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                \DB::table('working_files')->whereIn('working_id', $ud_status_workings)->update([
                    'status' => env('STATUS_UPLOADED'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            }
        } else {
            logfile('Da het file de upload len driver');
        }
    }

    public function updateOrderSku()
    {
        echo "<pre>";
        $woo_infos = \DB::table('woo_infos')->pluck('sku','id');
        print_r($woo_infos);
        die();
    }
    /*End Fulfillment*/
}
