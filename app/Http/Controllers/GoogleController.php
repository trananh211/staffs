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

        $path = 'https://molofa.com/wp-content/uploads/2020/02/mc1-5.png';
        $thumb = genThumb('test_'.date("YmdHms"), $path, env('THUMB'));
        var_dump($thumb);
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
        $path_export = env('DIR_EXCEL_EXPORT');
        if (!File::exists(storage_path($path_export))) {
//            exec ("find /path/to/folder -type d -exec chmod 0770 {} +");//for sub directory
//            exec ("find /path/to/folder -type f -exec chmod 0644 {} +");//for files inside directory
            exec("find ".$path_export." -type f -exec chmod 0777 {} +");//for files inside directory
        }
        foreach ($paths as $path) {
            if (!File::exists(storage_path($path))) {
                File::makeDirectory(storage_path($path), $mode = 0777, true, true);
            }
        }
        $lists = \DB::table('workings')
            ->join('woo_orders as wd', 'workings.woo_order_id', '=', 'wd.id')
            ->leftjoin('woo_product_drivers as wpd_goog', function ($join) {
                $join->on('wd.product_id', '=', 'wpd_goog.woo_product_id');
                $join->on('wd.woo_info_id', '=', 'wpd_goog.store_id');
            })
            ->select(
                'workings.id as working_id',
                'wd.id as woo_order_id', 'wd.order_status', 'wd.product_name', 'wd.number', 'wd.fullname', 'wd.address',
                'wd.city', 'wd.phone', 'wd.postcode', 'wd.country', 'wd.state', 'wd.status as fulfill_status',
                'wd.quantity', 'wd.customer_note', 'wd.email', 'wd.fullname', 'wd.sku', 'wd.sku_number',
                'wd.variation_detail', 'wd.variation_full_detail', 'wd.woo_info_id as store_id',
                'wd.product_name as product_origin_name', 'wd.product_id',
                'wpd_goog.template_id'
            )
            ->where([
                ['workings.status', '=', env('STATUS_WORKING_DONE')]
            ])
            ->orderBy('workings.woo_order_id', 'ASC')
            ->get();
        $lists_order = $this->preProductFulfill($lists);
        if ($lists_order) {
            $this->startFulfillExcel($lists_order);
        } else {
            logfile('-- Đã hết đơn hàng Custom để fulfill. Chuyển sang đơn hàng product auto.');
            $this->fullfillAutoProduct();
        }
    }

    private function fullfillAutoProduct()
    {
        $where = [
            ['wd.status', '=', env('STATUS_WORKING_NEW')],
            ['wd.custom_status', '=', env('STATUS_P_AUTO_PRODUCT')]
        ];
        $list_auto = \DB::table('woo_orders as wd')
            ->leftjoin('woo_product_drivers as wpd_goog', function ($join) {
                $join->on('wd.product_id', '=', 'wpd_goog.woo_product_id');
                $join->on('wd.woo_info_id', '=', 'wpd_goog.store_id');
            })
            ->select(
                'wd.id as woo_order_id', 'wd.order_status', 'wd.product_name', 'wd.number', 'wd.fullname', 'wd.address',
                'wd.city', 'wd.phone', 'wd.postcode', 'wd.country', 'wd.state', 'wd.status as fulfill_status',
                'wd.quantity', 'wd.customer_note', 'wd.email', 'wd.fullname', 'wd.sku', 'wd.sku_number',
                'wd.variation_detail', 'wd.variation_full_detail', 'wd.woo_info_id as store_id',
                'wd.product_name as product_origin_name', 'wd.product_id',
                'wpd_goog.template_id'
            )
            ->where($where)
            ->get();
        $lists = $this->preProductFulfill($list_auto);
        if ($lists) {
            $this->startFulfillAutoProductExcel($lists);
        } else {
            logfile('-- Đã hết đơn hàng để fulfill. Kết thúc.');
        }
    }

    private function preProductFulfill($lists)
    {
        if (sizeof($lists) > 0) {
            $lists = json_decode(json_encode($lists), true);
            $templates = \DB::table('woo_templates as w_tem')
                ->leftjoin('suppliers', 'w_tem.supplier_id', '=', 'suppliers.id')
                ->select(
                    'w_tem.id', 'w_tem.template_id', 'w_tem.supplier_id', 'w_tem.store_id', 'w_tem.variation_change_id',
                    'suppliers.path as supplier_path', 'suppliers.name as supplier_name'
                )
                ->get();
            $templates = json_decode(json_encode($templates), true);
            if (sizeof($templates) == 0) {
                logfile('-- Chưa có template thì không thể fulfill tự động. Mời bạn fulfill bằng tay.');
            } else {
                //Chuẩn bị dữ liệu để tạo file excel
                $lst_templates = array();
                foreach ($templates as $template) {
                    $key = $template['store_id'] . '_' . $template['template_id'];
                    $lst_templates[$key] = $template;
                }
                $ar_variation_changes_id = array();
                $check_again = array();
                //tìm các order working xem thuộc template và supplier nào.
                foreach ($lists as $k => $list) {
                    $list['product_origin_name'] = sanitizer($list['product_origin_name']);
                    /*Nếu khách chưa trả tiền. Kiểm tra lại với shop*/
                    if (in_array($list['order_status'], array('failed', 'cancelled', 'canceled', 'pending'))) {
                        logfile('-- Đơn hàng ' . $list['number'] . ' chưa thanh toán tiền');
                        if ($list['fulfill_status'] == env('STATUS_NOTFULFILL'))
                        {
                            unset($lists[$k]);
                            continue;
                        }
                        if (in_array($list['woo_order_id'], $check_again)) continue;
                        $check_again[] = $list['woo_order_id'];
                        unset($lists[$k]);
                        continue;
                    } else {
                        $key = $list['store_id'] . '_' . $list['template_id'];
                        if (array_key_exists($key, $lst_templates)) {
                            $lists[$k]['supplier_id'] = $lst_templates[$key]['supplier_id'];
                            $lists[$k]['supplier_path'] = $lst_templates[$key]['supplier_path'];
                            $lists[$k]['supplier_name'] = $lst_templates[$key]['supplier_name'];
                            $lists[$k]['variation_change_id'] = $lst_templates[$key]['variation_change_id'];
                            if ($lst_templates[$key]['variation_change_id'] != '') {
                                $ar_variation_changes_id[] = $lst_templates[$key]['variation_change_id'];
                            }
                        } else {
                            $lists[$k]['supplier_id'] = '';
                            $lists[$k]['supplier_path'] = '';
                            $lists[$k]['supplier_name'] = '';
                            $lists[$k]['variation_change_id'] = '';
                        }
                        $lists[$k]['variation_old'] = '';
                        $lists[$k]['variation_new'] = '';
                        $lists[$k]['variation_sku'] = '';
                    }
                }
                //kiểm tra xem có phải change variation hay không
                if (sizeof($ar_variation_changes_id) > 0) {
                    $lst_variation_changes = \DB::table('variation_change_items')
                        ->select('id', 'variation_change_id', 'variation_old_slug', 'variation_old', 'variation_new', 'variation_sku')
                        ->whereIn('variation_change_id', $ar_variation_changes_id)
                        ->get();
                    $lst_variation_changes = json_decode(json_encode($lst_variation_changes), true);
                    $get_variation_changes = array();
                    foreach ($lst_variation_changes as $lst) {
                        $key = $lst['variation_change_id'] . '_' . $lst['variation_old_slug'];
                        $get_variation_changes[$key] = $lst;
                    }
                    //compare with list product
                    foreach ($lists as $k => $list) {
                        $key = $list['variation_change_id'] . '_' . $list['variation_detail'];
                        if (array_key_exists($key, $get_variation_changes)) {
                            $lists[$k]['variation_old'] = $get_variation_changes[$key]['variation_old'];
                            $lists[$k]['variation_new'] = $get_variation_changes[$key]['variation_new'];
                            $lists[$k]['variation_sku'] = $get_variation_changes[$key]['variation_sku'];
                        }
                    }
                }
                // Phân loại các sản phẩm vào từng supplier của mình. Nếu sản phẩm nào không có supplier thì để supplier riêng
                $list_orders = array();
                $custom_path = env('GOOGLE_SUP_CUSTOM_FOLDER');
                $custom_supplier = \DB::table('suppliers')->select('id', 'name', 'path')->where('name', 'Custom')->first();
                if ($custom_supplier != null) {
                    $custom_path = $custom_supplier->path;
                }
                foreach ($lists as $list) {
                    if ($list['supplier_id'] == '') {
                        $list_orders[0]['supplier_name'] = 'Custom';
                        $list_orders[0]['path'] = $custom_path;
                        $list_orders[0]['data'][] = $list;
                    } else {
                        $list_orders[$list['supplier_id']]['supplier_name'] = $list['supplier_name'];
                        $list_orders[$list['supplier_id']]['path'] = $list['supplier_path'];
                        $list_orders[$list['supplier_id']]['data'][] = $list;
                    }
                }
                /*Nếu phát hiện ra có đơn hàng chưa trả tiền. Kiểm tra lại và fulfill vào ngày hôm sau*/
                if (sizeof($check_again) > 0) {
                    \DB::table('woo_orders')->whereIn('id', $check_again)->update(['status' => env('STATUS_NOTFULFILL')]);
                }
                //Kết thúc Chuẩn bị dữ liệu để tạo file excel
                return $list_orders;
            }
        } else {
            return false;
        }
    }

    private function startFulfillExcel($lists)
    {
        $today = date('m_d_Y');
        $ar_file_fulfill = [];
        $ar_order_fulfill = [];

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

        foreach ($lists as $supplier_id => $list_orders) {
            //kiểm tra tồn tại trên google driver
            $check = getDirExist($today, '', $list_orders['path']);
            if (!$check) {
                $path_google_dir = createDir($today, $list_orders['path']);
            } else {
                $path_google_dir = $check['path'];
            }
            $file_excel_name = str_replace(" ", '_', $list_orders['supplier_name']) . '_' . $today;
            $dt = array();
            $result_upload = false;
            foreach ($list_orders['data'] as $list) {
                $dt[] = [
                    'Order Number' => $list['number'],
                    'Tracking' => '',
                    'Product Name' => $list['product_origin_name'],
                    'SKU' => $list['sku'] . $list['variation_sku'],
                    'Variation' => str_replace('-;-;-', ' | ', $list['variation_full_detail']),
                    'Quantity' => $list['quantity'],
                    'Customer Note' => $list['customer_note'],
                    'OrderId' => $list['number'] . '-' . $list['woo_order_id'],
                    'Full Name' => $list['fullname'],
                    'Address' => $list['address'],
                    'City' => $list['city'],
                    'State Code' => $list['state'],
                    'Country Code' => $list['country'],
                    'Postcode' => $list['postcode'],
                    'Phone' => $list['phone'],
                    'Email' => $list['email']
                ];
                //list toàn bộ file working đã fulfill thành công để cập nhật trạng thái.
                $ar_file_fulfill[$list['supplier_id']][] = $list['working_id'];
                $ar_order_fulfill[$list['supplier_id']][] = $list['woo_order_id'];
                logfile('-- Đang fulfill đơn ' . $list['number'] . ' vào excel');
            }
            //tạo file excel dưới local
            $check = $this->makeExcel($file_excel_name, $dt);
            if ($check) {
                //up file excel lên google driver
                $check_up = upFile($check['full'], $path_google_dir);
                if ($check_up) {
                    $result_upload = true;
                    // Kiểm tra files đã từng up lên google driver hay chưa
                    if (array_key_exists($file_excel_name, $files_fulfillment)) {
                        $ar_del_gg_files[] = $files_fulfillment[$file_excel_name];
                    }
                    $new_gg_files[] = [
                        'name' => $file_excel_name,
                        'path' => $check_up,
                        'parent_path' => $path_google_dir,
                        'type' => 2,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                    // gom tất cả file working thành công vào 1 mảng
                    $ud_working_move = array_merge($ud_working_move, $ar_file_fulfill[$list['supplier_id']]);
                    $ud_order_move = array_merge($ud_order_move, $ar_order_fulfill[$list['supplier_id']]);
                    logfile('-- Fulfill file excel thành công của supplier: ' . $list_orders['supplier_name'] . ' số lượng: ' . sizeof($dt));
                }
//                    \File::delete($check['full']);
            }

        }
        $ud_working_move = array_unique($ud_working_move);
        $ud_order_move = array_unique($ud_order_move);

        //Nếu tồn tại file đã up lên trước đó rồi. Xóa trên google driver và xóa ở dưới database trước
        if (sizeof($ar_del_gg_files) > 0) {
            $files_del_fulfillment = \DB::table('gg_files')
                ->select('name', 'path', 'parent_path')
                ->whereIn('id', $ar_del_gg_files)
                ->get();
            foreach ($files_del_fulfillment as $file) {
                deleteFile($file->name . '.csv', $file->path, $file->parent_path);
                logfile('-- Tồn tại file :' . $file->name . '.csv trước đó rồi. Đang xóa trên thư mục Drive Google');
            }
            $result = \DB::table('gg_files')->whereIn('id', $ar_del_gg_files)->delete();
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

        /*Nếu export file excel thành công. Tiến hành cập nhật file workings và move lên google driver*/
        if (sizeof($ud_working_move) > 0) {
            \DB::table('workings')->whereIn('id', $ud_working_move)
                ->update([
                    'status' => env('STATUS_WORKING_MOVE'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            $check_order_status = \DB::table('workings')
                ->where('status', '<', env('STATUS_WORKING_MOVE'))
                ->whereIn('woo_order_id', $ud_order_move)
                ->pluck('woo_order_id')
                ->toArray();
            foreach ($ud_order_move as $k => $woo_order_id) {
                if (in_array($woo_order_id, $check_order_status)) {
                    unset($ud_order_move[$k]);
                }
            }
            if (sizeof($ud_order_move) > 0) {
                \DB::table('woo_orders')->whereIn('id', $ud_order_move)
                    ->update([
                        'status' => env('STATUS_WORKING_MOVE'),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
            }
        }
    }

    private function startFulfillAutoProductExcel($lists)
    {
        $today = date('m_d_Y');
        $ar_order_fulfill = [];

        $ud_order_move = array();
        $new_gg_files = array();
        $files_fulfillment = \DB::table('gg_files')
            ->where('type', 2)
            ->whereDate('updated_at', date("Y-m-d"))
            ->pluck('id', 'name')
            ->toArray();
        $ar_del_gg_files = array();
        $check2 = false;

        foreach ($lists as $supplier_id => $list_orders) {
            //kiểm tra tồn tại trên google driver
            $check = getDirExist($today, '', $list_orders['path']);
            if (!$check) {
                $path_google_dir = createDir($today, $list_orders['path']);
            } else {
                $path_google_dir = $check['path'];
            }
            $file_excel_name = str_replace(" ", '_', $list_orders['supplier_name']) . '_' . $today;
            $dt = array();
            $result_upload = false;
            foreach ($list_orders['data'] as $list) {
                $dt[] = [
                    'Order Number' => $list['number'],
                    'Tracking' => '',
                    'Product Name' => $list['product_origin_name'],
                    'SKU' => $list['sku'] . $list['variation_sku'],
                    'Variation' => str_replace('-;-;-', ' | ', $list['variation_full_detail']),
                    'Quantity' => $list['quantity'],
                    'Customer Note' => $list['customer_note'],
                    'OrderId' => $list['number'] . '-' . $list['woo_order_id'],
                    'Full Name' => $list['fullname'],
                    'Address' => $list['address'],
                    'City' => $list['city'],
                    'State Code' => $list['state'],
                    'Country Code' => $list['country'],
                    'Postcode' => $list['postcode'],
                    'Phone' => $list['phone'],
                    'Email' => $list['email']
                ];
                //list toàn bộ file working đã fulfill thành công để cập nhật trạng thái.
                $ar_order_fulfill[$list['supplier_id']][] = $list['woo_order_id'];
                logfile('-- Đang fulfill đơn ' . $list['number'] . ' vào excel');
            }
            //tạo file excel dưới local
            $check = $this->makeExcel($file_excel_name, $dt);
            if ($check) {
                //up file excel lên google driver
                $check_up = upFile($check['full'], $path_google_dir);
                if ($check_up) {
                    $result_upload = true;
                    // Kiểm tra files đã từng up lên google driver hay chưa
                    if (array_key_exists($file_excel_name, $files_fulfillment)) {
                        $ar_del_gg_files[] = $files_fulfillment[$file_excel_name];
                    }
                    $new_gg_files[] = [
                        'name' => $file_excel_name,
                        'path' => $check_up,
                        'parent_path' => $path_google_dir,
                        'type' => 2,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                    // gom tất cả file working thành công vào 1 mảng
                    $ud_order_move = array_merge($ud_order_move, $ar_order_fulfill[$list['supplier_id']]);
                    logfile('-- Fulfill file excel thành công của supplier:' . $list_orders['supplier_name'] . ' số lượng: ' . sizeof($dt));
                }
//                    \File::delete($check['full']);
            }

        }
        $ud_order_move = array_unique($ud_order_move);

        //Nếu tồn tại file đã up lên trước đó rồi. Xóa trên google driver và xóa ở dưới database trước
        if (sizeof($ar_del_gg_files) > 0) {
            $files_del_fulfillment = \DB::table('gg_files')
                ->select('name', 'path', 'parent_path')
                ->whereIn('id', $ar_del_gg_files)
                ->get();
            foreach ($files_del_fulfillment as $file) {
                deleteFile($file->name . '.csv', $file->path, $file->parent_path);
                logfile('-- Tồn tại file :' . $file->name . '.csv trước đó rồi. Đang xóa trên thư mục Drive Google');
            }
            $result = \DB::table('gg_files')->whereIn('id', $ar_del_gg_files)->delete();
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

        /*Nếu export file excel thành công. Tiến hành cập nhật trang thai order*/
        if (sizeof($ud_order_move) > 0) {
            \DB::table('woo_orders')->whereIn('id', $ud_order_move)
                ->update([
                    'status' => env('STATUS_WORKING_MOVE'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
        }
    }

    private static function makeExcel($name, $data)
    {
        $path = storage_path(env('DIR_EXCEL_EXPORT')) . '/' . $name . '.csv';
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
        })->store('csv', storage_path(env('DIR_EXCEL_EXPORT')), true);
        return $check;
    }

    public function uploadFileDriver()
    {
        $return = false;
        $lists = \DB::table('workings')
            ->join('woo_orders as wod', 'workings.woo_order_id', '=', 'wod.id')
            ->join('working_files as file', 'workings.id', '=', 'file.working_id')
            ->leftjoin('woo_product_drivers as wpd_goog', function ($join) {
                $join->on('wod.product_id', '=', 'wpd_goog.woo_product_id');
                $join->on('wod.woo_info_id', '=', 'wpd_goog.store_id');
            })
            ->select(
                'workings.id as working_id',
                'wod.id as woo_order_id', 'wod.sku', 'wod.woo_info_id as store_id', 'wod.number',
                'file.name as filename', 'file.path', 'file.id as working_file_id', 'file.is_mockup',
                'wpd_goog.template_id'
            )
            ->where('workings.status', env('STATUS_WORKING_MOVE'))
            ->where('file.status', env('STATUS_WORKING_DONE'))
            ->limit(env('GOOGLE_LIMIT_UPLOAD_FILE'))
            ->get();
        $lists = json_decode(json_encode($lists), true);
        if (sizeof($lists) > 0) {
            $data = $this->preDataUpload($lists);
            if ($data) {
                //Kết thúc Chuẩn bị dữ liệu để tạo file excel
                $pre_lists = $this->prePathUpload($data['list_files_upload'], $data['lst_suppliers']);
                if (sizeof($pre_lists) > 0) {
                    $this->startUploadFileDriver($pre_lists);
                } else {
                    logfile('--[Error] Xảy ra lỗi. Không thể chuẩn bị dữ liệu upload file');
                }
            } else {
                logfile('-- Xảy ra lỗi. Không thể upload file lên vào thời điểm này');
            }
        } else {
            logfile('-- Đã hết file custom để Upload fulfill. Chuyển sang fulfill file Product Auto');
            $return = $this->uploadProductAutoToDriver();
        }
        return $return;
    }

    public function uploadProductAutoToDriver()
    {
        $return = false;
        $where = [
            ['wd.status', '=', env('STATUS_WORKING_MOVE')],
            ['wd.custom_status', '=', env('STATUS_P_AUTO_PRODUCT')]
        ];
        $list_auto = \DB::table('woo_orders as wd')
            ->leftjoin('woo_product_drivers as wpd_goog', function ($join) {
                $join->on('wd.product_id', '=', 'wpd_goog.woo_product_id');
                $join->on('wd.woo_info_id', '=', 'wpd_goog.store_id');
            })
            ->select(
                'wd.id as woo_order_id', 'wd.sku', 'wd.sku_number',
                'wd.woo_info_id as store_id',
                'wd.product_name as product_origin_name', 'wd.product_id',
                'wpd_goog.template_id', 'wpd_goog.name as gg_dir_name', 'wpd_goog.path as gg_path'
            )
            ->where($where)
            ->get();
        if (sizeof($list_auto) > 0) {
            $data = $this->preDataUpload($list_auto);
            if ($data) {
                //Kết thúc Chuẩn bị dữ liệu để tạo file excel
                $pre_lists = $this->prePathUpload($data['list_files_upload'], $data['lst_suppliers']);
                if (sizeof($pre_lists) > 0) {
                    $this->startUploadProductAutoDriver($pre_lists);
                } else {
                    logfile('--[Error] Xảy ra lỗi. Không thể chuẩn bị dữ liệu upload file');
                }

            } else {
                logfile('-- Xảy ra lỗi. Không thể upload file lên vào thời điểm này');
            }
        } else {
            $return = true;
            logfile(' -- Đã hết sản phẩm auto để upload fullfill.');
        }
        return $return;
    }

    private function preDataUpload($lists)
    {
        $lists = json_decode(json_encode($lists), true);
        $templates = \DB::table('woo_templates as w_tem')
            ->leftjoin('suppliers', 'w_tem.supplier_id', '=', 'suppliers.id')
            ->select(
                'w_tem.id', 'w_tem.template_id', 'w_tem.supplier_id', 'w_tem.store_id',
                'suppliers.path as supplier_path', 'suppliers.name as supplier_name'
            )
            ->get();
        $templates = json_decode(json_encode($templates), true);
        if (sizeof($templates) == 0) {
            logfile('-- Chưa có template thì không thể Upload file tự động. Mời bạn Upload bằng tay.');
            return false;
        } else {
            //Chuẩn bị dữ liệu để tạo file excel
            $lst_templates = array();
            foreach ($templates as $template) {
                $key = $template['store_id'] . '_' . $template['template_id'];
                $lst_templates[$key] = $template;
            }
            $ar_variation_changes_id = array();
            $check_again = array();
            $lst_suppliers = [];
            //tìm các order working xem thuộc template và supplier nào.
            foreach ($lists as $k => $list) {
                $key = $list['store_id'] . '_' . $list['template_id'];
                if (array_key_exists($key, $lst_templates)) {
                    $lists[$k]['supplier_id'] = $lst_templates[$key]['supplier_id'];
                    $lists[$k]['supplier_path'] = $lst_templates[$key]['supplier_path'];
                    $lists[$k]['supplier_name'] = $lst_templates[$key]['supplier_name'];
                } else {
                    $lists[$k]['supplier_id'] = '';
                    $lists[$k]['supplier_path'] = '';
                    $lists[$k]['supplier_name'] = '';
                }
            }
            // Phân loại các sản phẩm vào từng supplier của mình. Nếu sản phẩm nào không có supplier thì để supplier riêng
            $list_files_upload = array();
            $custom_path = env('GOOGLE_SUP_CUSTOM_FOLDER');
            $custom_supplier = \DB::table('suppliers')->select('id', 'name', 'path')->where('name', 'Custom')->first();
            if ($custom_supplier != null) {
                $custom_path = $custom_supplier->path;
            }
            foreach ($lists as $list) {
                if ($list['supplier_id'] == '') {
                    $list_files_upload[0]['supplier_name'] = 'Custom';
                    $list_files_upload[0]['path'] = $custom_path;
                    $list_files_upload[0]['data'][] = $list;
                } else {
                    $list_files_upload[$list['supplier_id']]['supplier_name'] = $list['supplier_name'];
                    $list_files_upload[$list['supplier_id']]['path'] = $list['supplier_path'];
                    $list_files_upload[$list['supplier_id']]['data'][] = $list;
                }
                $lst_suppliers[$list['supplier_id']] = $list['supplier_id'];
            }
            return array(
                'list_files_upload' => $list_files_upload,
                'lst_suppliers' => $lst_suppliers
            );
        }
    }

    private function prePathUpload($lists, $lst_supplier_id)
    {
        $today = date('m_d_Y');
        $google_folder = array();
        $data_gg_folders = array();
        $data_upload_files = array();
        //Tìm tất cả các folder được update ngày hôm nay theo danh sách supplier
        $lst_google_folder = \DB::table('gg_folders')
            ->select('id', 'name', 'supplier_id', 'path')
            ->whereIn('supplier_id', $lst_supplier_id)
            ->where('date_fulfill', $today)
            ->get();
        $lst_google_folder = json_decode(json_encode($lst_google_folder), true);
        $compare_google_folder = array();
        foreach ($lst_google_folder as $info) {
            $key = ($info['supplier_id'] == 0) ? '_' . $info['name'] : $info['supplier_id'] . '_' . $info['name'];
            $compare_google_folder[$key] = $info['path'];
        }
        // Bắt đầu so sánh và tạo thư mục mới trên google driver
        foreach ($lists as $supplier_id => $list_files) {
            //Kiểm tra đã tồn tại thu mục ngày hay chưa. Nếu chưa thì không làm gì nữa.
            $result_checkDir = getDirExist($today, '', $list_files['path']);
            if (!$result_checkDir) {
                $str = "-- Chưa tồn tại thư mục ngày. Không up hàng và để Fulfill lại file excel";
                logfile($str);
                continue;
            }
            $gg_path_date = $result_checkDir['path'];
            //Tạo mới thư mục google driver folder để insert file vào.
            foreach ($list_files['data'] as $k => $list) {
                $key = $list['supplier_id'] . "_" . $list['sku'];
                //Kiểm tra đã tồn tại ở trên google driver hay chưa
                if (array_key_exists($key, $compare_google_folder)) {
                    $path_gg_product = $compare_google_folder[$key];
                } else {
                    // Nếu chưa tồn tại thư mục Sku thì tạo mới
                    if (!array_key_exists($key, $google_folder)) {
                        $path_gg_product = createDir($list['sku'], $gg_path_date);
                        $google_folder[$key] = $path_gg_product;
                        $data_gg_folders[] = [
                            'name' => $list['sku'],
                            'path' => $path_gg_product,
                            'parent_path' => $gg_path_date,
                            'dir' => $today . '/' . $list['sku'],
                            'supplier_id' => $supplier_id,
                            'date_fulfill' => $today,
                            'level' => 3,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                    } else {
                        $path_gg_product = $google_folder[$key];
                    }
                }
                $lists[$supplier_id]['data'][$k]['path_gg_driver'] = $path_gg_product;
                $list['path_gg_driver'] = $path_gg_product;
                $data_upload_files[] = $list;
            }
        }
        if (sizeof($data_gg_folders) > 0) {
            \DB::table('gg_folders')->insert($data_gg_folders);
        }
        return $data_upload_files;
    }

    private function startUploadProductAutoDriver($lists)
    {
        $lst_orders_done = array();
        foreach ($lists as $list) {
            //list ra danh sách file ơ thư mục product auto
            $files = scanFolder($list['gg_path']);
            $num = 0;
            $copy = 0;
            if ($files) {
                foreach ($files as $file) {
                    $file_name = $file['filename'];
                    //kiểm tra xem có phải file mc hoặc mk hay không
                    if (strpos($file_name, 'mc') !== false || strpos($file_name, 'mk') !== false) {
                        if ($num >= 1) {
                            continue;
                        }
                        $num += 1;
                    }
                    $new_name = $list['sku'].'_'.$file['name'];
                    //Kiểm tra xem file đã tồn tại chưa
                    $check_exist_before = checkFileExist($new_name, $list['path_gg_driver']);
                    if (!$check_exist_before)
                    {
                        $result = Storage::cloud()->copy($file['path'], $list['path_gg_driver'].'/'.$new_name);
                        if ($result)
                        {
                            $copy++;
                            logfile(' -- [Copy] Thành công file '.$file['name'].' của sản phẩm '.$list['product_origin_name']);
                        } else {
                            logfile(' -- [Error Copy] Không thể copy file '.$file['name'].' của sản phẩm '.$list['product_origin_name']);
                        }
                    } else {
                        $copy++;
                    }
                }
            } else {
                logfile('-- [Error] Không thể quét được file của product ' . $list['product_origin_name'] . ' .Bỏ qua.');
            }
            if ($copy > 0)
            {
                $lst_orders_done[] = $list['woo_order_id'];
            } else {
                // Xóa thư mục lỗi trên google driver
                deleteDir($list['sku'], dirname($list['path_gg_driver']));
            }

        }
        //cập nhật trạng thái woo_order fulfill thành công
        if (sizeof($lst_orders_done) > 0)
        {
            \DB::table('woo_orders')->whereIn('id',$lst_orders_done)->update([
                'status' => env('STATUS_UPLOADED'),
                'updated_at' => date("Y-m-d H:i:s")
            ]);
        }
        logfile('-- [Done] Kết thúc fullfill copy file lên thư mục google driver');
    }

    private function startUploadFileDriver($data_upload_files)
    {
        // Bắt đầu upload file lên google driver
        if (sizeof($data_upload_files) > 0) {
            $ud_status_working_files = [];
            $ud_status_workings = [];
            $ud_status_orders = [];
            $db_google_files = [];
            foreach ($data_upload_files as $file) {
                $parent_path = $file['path_gg_driver'];
                $dir_info = public_path($file['path'] . $file['filename']);
                $new_name = $file['sku'] . '_' . $file['filename'];
                //đổi tên file trùng với sku
                $str_file_old = $file['number'].'-PID-'.$file['working_id'];
                $tmp = explode($str_file_old, $new_name);
                if (sizeof($tmp) > 1)
                {
                    $new_name = preg_replace('([\s]+)', '', implode('',$tmp));
                }
                $path = upFile($dir_info, $parent_path, $new_name);
                if ($path) {
                    logfile('-- Up thành công file ' . $file['filename'] . ' lên google Driver');
                    $db_google_files[] = [
                        'name' => $new_name,
                        'path' => $path,
                        'parent_path' => $parent_path,
                        'working_file_id' => $file['working_file_id'],
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                    $ud_status_working_files[$file['working_file_id']] = $file['working_file_id'];
                    $ud_status_workings[$file['working_id']] = $file['working_id'];
                    $ud_status_orders[$file['woo_order_id']] = $file['woo_order_id'];
                } else {
                    logfile('-- Up thất bại file ' . $file['filename'] . ' lên google Driver');
                }
            }

            if (sizeof($ud_status_working_files) > 0) {
                \DB::table('gg_files')->insert($db_google_files);

                \DB::table('working_files')->whereIn('id', $ud_status_working_files)->update([
                    'status' => env('STATUS_UPLOADED'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $check_working_status = \DB::table('working_files')
                    ->where('status', '<', env('STATUS_UPLOADED'))
                    ->whereIn('working_id', $ud_status_workings)
                    ->pluck('working_id')
                    ->toArray();
                foreach ($ud_status_workings as $k => $working_id) {
                    if (in_array($working_id, $check_working_status)) {
                        unset($ud_status_workings[$k]);
                    }
                }
                if (sizeof($ud_status_workings) > 0) {
                    \DB::table('workings')->whereIn('id', $ud_status_workings)->update([
                        'status' => env('STATUS_UPLOADED'),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);

                    $check_order_status = \DB::table('workings')
                        ->where('status', '<', env('STATUS_UPLOADED'))
                        ->whereIn('woo_order_id', $ud_status_orders)
                        ->pluck('woo_order_id')
                        ->toArray();
                    foreach ($ud_status_orders as $k => $woo_order_id) {
                        if (in_array($woo_order_id, $check_order_status)) {
                            unset($ud_status_orders[$k]);
                        }
                    }
                    if (sizeof($ud_status_orders) > 0) {
                        \DB::table('woo_orders')->whereIn('id', $ud_status_orders)
                            ->update([
                                'status' => env('STATUS_UPLOADED'),
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                    }
                }
                logfile('-- Fulfillment Thành công');
            }
        }
    }
    /*End Fulfillment*/

    public function uploadFileWorkingGoogle2()
    {
        logfile_system("======= Upload file design to Google Driver  =========================");
        $return = false;
        $categories = \DB::table('tool_categories')->select('id','name','base_name')
            ->get()->toArray();
        if (sizeof($categories) > 0)
        {
            $lst_categories = array();
            $update_categories = array();
            foreach ($categories as $category)
            {
                //neu chua ton tai folder tren google driver. Tao moi
                if($category->base_name == '')
                {
                    $check_exist = getDirExist($category->name, '', env('GOOGLE_DRIVER_FOLDER_JOB'));
                    if(!$check_exist){
                        try {
                            $new_dir = createDirFullInfo($category->name, env('GOOGLE_DRIVER_FOLDER_JOB'));
                            $update_categories[$category->id] = [
                                'parent_path' => $new_dir['dirname'],
                                'base_path' => $new_dir['path'],
                                'base_name' => $new_dir['basename'],
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            $lst_categories[$category->id] = [
                                'category_name' => $category->name,
                                'category_basename' => $new_dir['path']
                            ];
                        } catch (\Exception $e) {
                            logfile_system('-- Không thể tạo thư mục :'.$category->name.' trên driver vào thời điểm này');
                        }
                    } else {
                        $update_categories[$category->id] = [
                            'parent_path' => $check_exist['dirname'],
                            'base_path' => $check_exist['path'],
                            'base_name' => $check_exist['basename'],
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $lst_categories[$category->id] = [
                            'category_name' => $category->name,
                            'category_basename' => $check_exist['path']
                        ];
                    }
                } else {
                    $lst_categories[$category->id] = [
                        'category_name' => $category->name,
//                        'category_basename' => env('GOOGLE_DRIVER_FOLDER_JOB').'/'.$category->base_path
                        'category_basename' => $category->base_name
                    ];
                }
            }
            // cập nhật thông tin base path nếu có vào database
            if (sizeof($update_categories) > 0)
            {
                foreach ($update_categories as $category_id => $update)
                {
                    \DB::table('tool_categories')->where('id',$category_id)->update($update);
                }
            }
            // chuẩn bị category xong
            if (sizeof($lst_categories) > 0)
            {
                // lấy danh sách file cần upload
                $files = \DB::table('workings')
                    ->leftjoin('working_files as wf', 'workings.id', '=', 'wf.working_id')
                    ->leftjoin('designs', 'designs.id', '=', 'workings.design_id')
                    ->leftjoin('product_codes as pdc', 'designs.product_code_id', '=', 'pdc.id')
                    ->select(
                        'workings.id as working_id',
                        'wf.id as working_file_id', 'wf.name', 'wf.path','wf.base_name as working_base_name','wf.base_dirname as working_base_dirname',
                        'designs.id as design_id', 'designs.product_name', 'designs.tool_category_id',
                        'pdc.id as product_code_id', 'pdc.base_name as product_code_base_name'
                    )
                    ->where([
                        ['wf.status', '=', env('STATUS_WORKING_DONE')]
                    ])
                    ->limit(env('GOOGLE_LIMIT_UPLOAD_FILE'))
                    ->get()->toArray();
                // predata product code id
                if (sizeof($files) > 0)
                {
                    $lst_product_code = array();
                    $product_codes = array();
                    $update_designs = array();
                    $list_working_files = array();
                    foreach ($files as $file)
                    {
                        if($file->tool_category_id == '') {
                            continue;
                        }
                        if($file->product_code_id == '')
                        {
                            $tmp = explode(' ', $file->product_name);
                            $product_code = $tmp[sizeof($tmp) - 1];
                            if (!array_key_exists($product_code, $product_codes))
                            {
                                $product_codes[$product_code] = [
                                    'product_code' => $product_code,
                                    'dir_path' => $lst_categories[$file->tool_category_id]['category_basename']
                                ];
                            }
                            $lst_product_code[$product_code]['design'][$file->design_id] = $file->design_id;
                        }
                        if( $file->product_code_id != '')
                        {
                            $list_working_files[$file->product_code_id]['path'] = $file->product_code_base_name;
                            $list_working_files[$file->product_code_id]['info'][] = json_decode(json_encode($file, true), true);
                        }
                    }
                    // vẫn chưa chuẩn bị xong product code. cần chuẩn bị luôn
                    if (sizeof($lst_product_code) > 0)
                    {
                        // lấy danh sách product code
                        $list_codes = \DB::table('product_codes')->pluck('id','product_code')->toArray();
                        foreach ($product_codes as $product_code => $item)
                        {
                            $data_product_code = array();
                            // nếu chưa tồn tại product code. Tạo mới trên google driver. Lưu vào db
                            if( !array_key_exists($product_code, $list_codes))
                            {
                                $check_exist = getDirExist($product_code, '', $item['dir_path']);
                                // nếu tồn tại dir trên google driver rồi
                                if ($check_exist)
                                {
                                    $data_product_code = [
                                        'product_code' => $product_code,
                                        'parent_path' => $check_exist['dirname'],
                                        'base_path' => $check_exist['path'],
                                        'base_name' => $check_exist['basename'],
                                        'created_at' => date("Y-m-d H:i:s"),
                                        'updated_at' => date("Y-m-d H:i:s")
                                    ];
                                } else { // nếu chưa tồn tại dir trên google driver
                                    try {
                                        $new_dir = createDirFullInfo($product_code, $item['dir_path']);
                                        $data_product_code = [
                                            'product_code' => $product_code,
                                            'parent_path' => $new_dir['dirname'],
                                            'base_path' => $new_dir['path'],
                                            'base_name' => $new_dir['basename'],
                                            'created_at' => date("Y-m-d H:i:s"),
                                            'updated_at' => date("Y-m-d H:i:s")
                                        ];
                                    } catch (\Exception $e) {
                                        logfile_system('-- Không thể tạo thư mục : '.$product_code.' trên driver vào thời điểm này');
                                    }
                                }
                                // nếu tồn tại data để lưu vào db
                                if(sizeof($data_product_code) > 0)
                                {
                                    $product_code_id = \DB::table('product_codes')->insertGetId($data_product_code);
                                    $lst_product_code[$product_code]['product_code_id'] = $product_code_id;
                                }
                            } else { // nếu đã từng tồn tại product code trong database
                                if(array_key_exists($product_code, $lst_product_code))
                                {
                                    $lst_product_code[$product_code]['product_code_id'] = $list_codes[$product_code];
                                }
                            }
                        }

                        // nếu tồn tại thông tin về thư mục trên google driver để lưu vào table product code
                        if (sizeof($lst_product_code) > 0)
                        {
                            foreach ($lst_product_code as $item)
                            {
                                \DB::table('designs')->whereIn('id',$item['design'])
                                    ->update(['product_code_id' => $item['product_code_id']]);
                            }
                            logfile_system('-- Chuẩn bị thành công product code id. Chuyển sang tạo mới thư mục SKU');
                        }
                    } else { // đã chuẩn bị xong product code id vào design. Kiểm tra xem đã tạo mới
                        logfile_system('-- Tạo mới thư mục SKU');
//                        print_r($list_working_files);
                        $working_file_update = array();
                        $working_file_error = array();
                        $file_delete = array();
                        foreach ($list_working_files as $product_code_id => $info)
                        {
                            $parent_path = $info['path'];
                            foreach($info['info'] as $file)
                            {
                                $path = public_path($file['path'].$file['name']);
                                if (\File::exists($path)) {
                                    try {
                                        $check_exist = checkFileExistFullInfo($file['name'], $parent_path);
                                        if (!$check_exist)
                                        {
                                            $info = upFile_FullInfo($path, $parent_path);
                                        } else {
                                            $info = $check_exist;
                                        }
                                        $result = true;
                                    } catch (\Exception $e) {
                                        $result = false;
                                        logfile_system(' -- Xảy ra lỗi nội bộ : '.$e->getMessage());
                                    }
                                    if ($result) {
                                        // xóa file local đi cho đỡ nặng
                                        $file_delete[] = $path;
                                        // tao data working file google driver cap nhat vao database
                                        $base_name = $info['basename'];
                                        $base_path = $info['path'];
                                        $base_dirname = $info['dirname'];
//                                        $working_file_update[$file['working_file_id']] = [
//                                            'base_name' => $base_name,
//                                            'base_path' => $base_path,
//                                            'base_dirname' => $base_dirname,
//                                            'status' => env('STATUS_WORKING_MOVE'),
//                                            'updated_at' => date("Y-m-d H:i:s")
//                                        ];

                                        $update[] = [
                                            'base_name' => $base_name,
                                            'base_path' => $base_path,
                                            'base_dirname' => $base_dirname,
                                            'status' => env('STATUS_WORKING_MOVE'),
                                            'updated_at' => date("Y-m-d H:i:s")
                                        ];
                                        \DB::table('working_files')->where('id',$file['working_file_id'])->update($update);
                                        logfile_system('--- Upload thành công '.$file['name'].' lên google driver');
                                    } else {
                                        $working_file_error[] = $file['working_file_id'];
                                        logfile_system('--- Upload thất bại '.$file['name'].' lên google driver');
                                    }
                                }
                            }
                        }
                        // bat dau kiem tra lai
                        if (sizeof($file_delete) > 0)
                        {
                            \File::delete($file_delete);
                        }
                        if(sizeof($working_file_update) > 0)
                        {
                            foreach ($working_file_update as $working_file_id => $data_update)
                            {
                                \DB::table('working_files')->where('id',$working_file_id)->update($data_update);
                            }
                            logfile_system('-- Upload thành công '.sizeof($working_file_update).' files lên google driver');
                        }
                        if (sizeof($working_file_error) > 0)
                        {
                            logfile_system(' -- Xảy ra '.sizeof($working_file_error).' job bị lỗi. Không thể up lên google driver');
                            $update = [
                                'status' => env('STATUS_WORKING_ERROR'),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            \DB::table('working_files')->whereIn('id',$working_file_error)->update($update);
                        }
                    }
                } else {
                    logfile_system('-- Đã hết job hoàn thành cần up lên google driver. Chuyển sang công việc tiếp theo');
                    $return = true;
                }
            } else {
                logfile_system('-- Không thể lấy được data path để gửi file lên. Kiểm tra lại');
                $return = true;
            }
        } else {
            $return = true;
            logfile_system('-- Chưa tồn tại categories nào. Mời bạn tạo mới để fulfills');
        }
        return $return;
    }

    public function uploadFileWorkingGoogle()
    {
        logfile_system("======= Upload file design to Google Driver  =========================");
        $return = false;
        $categories = \DB::table('tool_categories')->select('id','name','base_name')
            ->get()->toArray();
        if (sizeof($categories) > 0)
        {
            $lst_categories = array();
            $update_categories = array();
            foreach ($categories as $category)
            {
                //neu chua ton tai folder tren google driver. Tao moi
                if($category->base_name == '')
                {
                    $check_exist = getDirExist($category->name, '', env('GOOGLE_DRIVER_FOLDER_JOB'));
                    if(!$check_exist){
                        try {
                            $new_dir = createDirFullInfo($category->name, env('GOOGLE_DRIVER_FOLDER_JOB'));
                            $update_categories[$category->id] = [
                                'parent_path' => $new_dir['dirname'],
                                'base_path' => $new_dir['path'],
                                'base_name' => $new_dir['basename'],
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            $lst_categories[$category->id] = [
                                'category_name' => $category->name,
                                'category_basename' => $new_dir['path']
                            ];
                        } catch (\Exception $e) {
                            logfile_system('-- Không thể tạo thư mục :'.$category->name.' trên driver vào thời điểm này');
                        }
                    } else {
                        $update_categories[$category->id] = [
                            'parent_path' => $check_exist['dirname'],
                            'base_path' => $check_exist['path'],
                            'base_name' => $check_exist['basename'],
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $lst_categories[$category->id] = [
                            'category_name' => $category->name,
                            'category_basename' => $check_exist['path']
                        ];
                    }
                } else {
                    $lst_categories[$category->id] = [
                        'category_name' => $category->name,
//                        'category_basename' => env('GOOGLE_DRIVER_FOLDER_JOB').'/'.$category->base_path
                        'category_basename' => $category->base_name
                    ];
                }
            }
            // cập nhật thông tin base path nếu có vào database
            if (sizeof($update_categories) > 0)
            {
                foreach ($update_categories as $category_id => $update)
                {
                    \DB::table('tool_categories')->where('id',$category_id)->update($update);
                }
            }
            // chuẩn bị category xong
            if (sizeof($lst_categories) > 0)
            {
                // lấy danh sách file cần upload
                $files = \DB::table('workings')
                    ->leftjoin('working_files as wf', 'workings.id', '=', 'wf.working_id')
                    ->leftjoin('designs', 'designs.id', '=', 'workings.design_id')
                    ->leftjoin('product_codes as pdc', 'designs.product_code_id', '=', 'pdc.id')
                    ->select(
                        'workings.id as working_id',
                        'wf.id as working_file_id', 'wf.name', 'wf.path','wf.base_name as working_base_name','wf.base_dirname as working_base_dirname',
                        'designs.id as design_id', 'designs.product_name', 'designs.tool_category_id',
                        'pdc.id as product_code_id', 'pdc.base_name as product_code_base_name'
                    )
                    ->where([
                        ['wf.status', '=', env('STATUS_WORKING_DONE')]
                    ])
                    ->limit(env('GOOGLE_LIMIT_UPLOAD_FILE'))
                    ->get()->toArray();
                // predata product code id
                if (sizeof($files) > 0)
                {
                    $lst_product_code = array();
                    $product_codes = array();
                    $update_designs = array();
                    $list_working_files = array();
                    foreach ($files as $file)
                    {
                        if($file->tool_category_id == '') {
                            continue;
                        }
                        if($file->product_code_id == '')
                        {
                            $tmp = explode(' ', $file->product_name);
                            $product_code = $tmp[sizeof($tmp) - 1];
                            if (!array_key_exists($product_code, $product_codes))
                            {
                                $product_codes[$product_code] = [
                                    'product_code' => $product_code,
                                    'dir_path' => $lst_categories[$file->tool_category_id]['category_basename']
                                ];
                            }
                            $lst_product_code[$product_code]['design'][$file->design_id] = $file->design_id;
                        }
                        if( $file->product_code_id != '')
                        {
                            $list_working_files[$file->product_code_id]['path'] = $file->product_code_base_name;
                            $list_working_files[$file->product_code_id]['info'][] = json_decode(json_encode($file, true), true);
                        }
                    }
                    // vẫn chưa chuẩn bị xong product code. cần chuẩn bị luôn
                    if (sizeof($lst_product_code) > 0)
                    {
                        // lấy danh sách product code
                        $list_codes = \DB::table('product_codes')->pluck('id','product_code')->toArray();
                        foreach ($product_codes as $product_code => $item)
                        {
                            $data_product_code = array();
                            // nếu chưa tồn tại product code. Tạo mới trên google driver. Lưu vào db
                            if( !array_key_exists($product_code, $list_codes))
                            {
                                $check_exist = getDirExist($product_code, '', $item['dir_path']);
                                // nếu tồn tại dir trên google driver rồi
                                if ($check_exist)
                                {
                                    $data_product_code = [
                                        'product_code' => $product_code,
                                        'parent_path' => $check_exist['dirname'],
                                        'base_path' => $check_exist['path'],
                                        'base_name' => $check_exist['basename'],
                                        'created_at' => date("Y-m-d H:i:s"),
                                        'updated_at' => date("Y-m-d H:i:s")
                                    ];
                                } else { // nếu chưa tồn tại dir trên google driver
                                    try {
                                        $new_dir = createDirFullInfo($product_code, $item['dir_path']);
                                        $data_product_code = [
                                            'product_code' => $product_code,
                                            'parent_path' => $new_dir['dirname'],
                                            'base_path' => $new_dir['path'],
                                            'base_name' => $new_dir['basename'],
                                            'created_at' => date("Y-m-d H:i:s"),
                                            'updated_at' => date("Y-m-d H:i:s")
                                        ];
                                    } catch (\Exception $e) {
                                        logfile_system('-- Không thể tạo thư mục : '.$product_code.' trên driver vào thời điểm này');
                                    }
                                }
                                // nếu tồn tại data để lưu vào db
                                if(sizeof($data_product_code) > 0)
                                {
                                    $product_code_id = \DB::table('product_codes')->insertGetId($data_product_code);
                                    $lst_product_code[$product_code]['product_code_id'] = $product_code_id;
                                }
                            } else { // nếu đã từng tồn tại product code trong database
                                if(array_key_exists($product_code, $lst_product_code))
                                {
                                    $lst_product_code[$product_code]['product_code_id'] = $list_codes[$product_code];
                                }
                            }
                        }

                        // nếu tồn tại thông tin về thư mục trên google driver để lưu vào table product code
                        if (sizeof($lst_product_code) > 0)
                        {
                            foreach ($lst_product_code as $item)
                            {
                                \DB::table('designs')->whereIn('id',$item['design'])
                                    ->update(['product_code_id' => $item['product_code_id']]);
                            }
                            logfile_system('-- Chuẩn bị thành công product code id. Chuyển sang tạo mới thư mục SKU');
                        }
                    } else { // đã chuẩn bị xong product code id vào design. Kiểm tra xem đã tạo mới
                        logfile_system('-- Tạo mới thư mục SKU');
                        // print_r($list_working_files);
                        $working_file_update = array();
                        $working_file_error = array();
                        $file_delete = array();
                        $update_error = array();
                        foreach ($list_working_files as $product_code_id => $item)
                        {
                            $parent_path = $item['path'];
                            foreach($item['info'] as $file)
                            {
                                $path = public_path($file['path'].$file['name']);
                                if (\File::exists($path)) {
                                    \DB::table('working_files')->where('id',$file['working_file_id'])
                                        ->update(['status' => 18]);
                                    logfile_system('--- Đang upload file: '.$file['name'].' lên google driver');
                                    try {
                                        $check_exist = checkFileExistFullInfo($file['name'], $parent_path);
                                        if (!$check_exist)
                                        {
                                            $info = upFile_FullInfo($path, $parent_path);
                                        } else {
                                            $info = $check_exist;
                                        }
                                        $result = true;
                                    } catch (\Exception $e) {
                                        $result = false;
                                        logfile_system(' -- Xảy ra lỗi nội bộ : '.$e->getMessage());
                                    }
                                    if ($result) {
                                        // xóa file local đi cho đỡ nặng
                                        $file_delete[] = $path;
                                        // tao data working file google driver cap nhat vao database
                                        $base_name = $info['basename'];
                                        $base_path = $info['path'];
                                        $base_dirname = $info['dirname'];

                                        $update = [
                                            'base_name' => $base_name,
                                            'base_path' => $base_path,
                                            'base_dirname' => $base_dirname,
                                            'status' => env('STATUS_WORKING_MOVE'),
                                            'updated_at' => date("Y-m-d H:i:s")
                                        ];
                                        \DB::table('working_files')->where('id',$file['working_file_id'])->update($update);
                                        logfile_system('--- Upload thành công '.$file['name'].' lên google driver');
                                    } else {
                                        $working_file_error[] = $file['working_file_id'];
                                        logfile_system('--- Upload thất bại '.$file['name'].' lên google driver');
                                    }
                                } else {
                                    logfile_system('--- Không tồn tại file '.$file['name'].' trên local');
                                    $update_error[] = $file['working_file_id'];
                                }
                            }
                        }
                        // bat dau kiem tra lai
                        if (sizeof($file_delete) > 0)
                        {
                            \File::delete($file_delete);
                        }

                        if (sizeof($update_error) > 0)
                        {
                            \DB::table('working_files')->whereIn('id',$update_error)->update(['status' => 36]);
                        }

                        if(sizeof($working_file_update) > 0)
                        {
                            foreach ($working_file_update as $working_file_id => $data_update)
                            {
                                \DB::table('working_files')->where('id',$working_file_id)->update($data_update);
                            }
                            logfile_system('-- Upload thành công '.sizeof($working_file_update).' files lên google driver');
                        }
                        if (sizeof($working_file_error) > 0)
                        {
                            logfile_system(' -- Xảy ra '.sizeof($working_file_error).' job bị lỗi. Không thể up lên google driver');
                            $update = [
                                'status' => env('STATUS_WORKING_ERROR'),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            \DB::table('working_files')->whereIn('id',$working_file_error)->update($update);
                        }
                    }
                } else {
                    logfile_system('-- Đã hết job hoàn thành cần up lên google driver. Chuyển sang công việc tiếp theo');
                    $return = true;
                }
            } else {
                logfile_system('-- Không thể lấy được data path để gửi file lên. Kiểm tra lại');
                $return = true;
            }
        } else {
            $return = true;
            logfile_system('-- Chưa tồn tại categories nào. Mời bạn tạo mới để fulfills');
        }
        return $return;
    }

    public function moveFileWorkingGoogle()
    {
        logfile_system("======= Move file design to Google Driver  =========================");
        $return = false;
        $categories = \DB::table('tool_categories')->select('id','name','base_name')
            ->get()->toArray();
        if (sizeof($categories) > 0)
        {
            $lst_categories = array();
            $update_categories = array();
            foreach ($categories as $category)
            {
                //neu chua ton tai folder tren google driver. Tao moi
                if($category->base_name == '')
                {
                    $check_exist = getDirExist($category->name, '', env('GOOGLE_DRIVER_FOLDER_JOB'));
                    if(!$check_exist){
                        try {
                            $new_dir = createDirFullInfo($category->name, env('GOOGLE_DRIVER_FOLDER_JOB'));
                            $update_categories[$category->id] = [
                                'parent_path' => $new_dir['dirname'],
                                'base_path' => $new_dir['path'],
                                'base_name' => $new_dir['basename'],
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            $lst_categories[$category->id] = [
                                'category_name' => $category->name,
                                'category_basename' => $new_dir['path']
                            ];
                        } catch (\Exception $e) {
                            logfile_system('-- Không thể tạo thư mục :'.$category->name.' trên driver vào thời điểm này');
                        }
                    } else {
                        $update_categories[$category->id] = [
                            'parent_path' => $check_exist['dirname'],
                            'base_path' => $check_exist['path'],
                            'base_name' => $check_exist['basename'],
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $lst_categories[$category->id] = [
                            'category_name' => $category->name,
                            'category_basename' => $check_exist['path']
                        ];
                    }
                } else {
                    $lst_categories[$category->id] = [
                        'category_name' => $category->name,
                        'category_basename' => $category->base_name
                    ];
                }
            }
            // cập nhật thông tin base path nếu có vào database
            if (sizeof($update_categories) > 0)
            {
                foreach ($update_categories as $category_id => $update)
                {
                    \DB::table('tool_categories')->where('id',$category_id)->update($update);
                }
            }
            // chuẩn bị category xong
            if (sizeof($lst_categories) > 0)
            {
                // lấy danh sách file cần upload
                $files = \DB::table('workings')
                    ->leftjoin('working_files as wf', 'workings.id', '=', 'wf.working_id')
                    ->leftjoin('designs', 'designs.id', '=', 'workings.design_id')
                    ->leftjoin('product_codes as pdc', 'designs.product_code_id', '=', 'pdc.id')
                    ->select(
                        'workings.id as working_id',
                        'wf.id as working_file_id', 'wf.name', 'wf.path','wf.base_name as working_base_name',
                        'wf.base_dirname as working_base_dirname',
                        'designs.id as design_id', 'designs.product_name', 'designs.tool_category_id',
                        'pdc.id as product_code_id', 'pdc.base_name as product_code_base_name'
                    )
                    ->where([
                        ['wf.status', '=', env('STATUS_WORKING_MOVE')]
                    ])
                    ->limit(env('GOOGLE_LIMIT_UPLOAD_FILE'))
                    ->get()->toArray();
                // predata product code id
                if (sizeof($files) > 0)
                {
                    $lst_product_code = array();
                    $product_codes = array();
                    $update_designs = array();
                    $list_working_files = array();
                    foreach ($files as $file)
                    {
                        if($file->tool_category_id == '') {
                            continue;
                        }
                        if($file->product_code_id == '')
                        {
                            $tmp = explode(' ', $file->product_name);
                            $product_code = $tmp[sizeof($tmp) - 1];
                            if (!array_key_exists($product_code, $product_codes))
                            {
                                $product_codes[$product_code] = [
                                    'product_code' => $product_code,
                                    'dir_path' => $lst_categories[$file->tool_category_id]['category_basename']
                                ];
                            }
                            $lst_product_code[$product_code]['design'][$file->design_id] = $file->design_id;
                        }
                        if( $file->product_code_id != '')
                        {
                            $list_working_files[$file->product_code_id]['path'] = $file->product_code_base_name;
                            $list_working_files[$file->product_code_id]['info'][] = json_decode(json_encode($file, true), true);
                        }
                    }
                    // vẫn chưa chuẩn bị xong product code. cần chuẩn bị luôn
                    if (sizeof($lst_product_code) > 0)
                    {
                        // lấy danh sách product code
                        $list_codes = \DB::table('product_codes')->pluck('id','product_code')->toArray();
                        foreach ($product_codes as $product_code => $item)
                        {
                            $data_product_code = array();
                            // nếu chưa tồn tại product code. Tạo mới trên google driver. Lưu vào db
                            if( !array_key_exists($product_code, $list_codes))
                            {
                                $check_exist = getDirExist($product_code, '', $item['dir_path']);
                                // nếu tồn tại dir trên google driver rồi
                                if ($check_exist)
                                {
                                    $data_product_code = [
                                        'product_code' => $product_code,
                                        'parent_path' => $check_exist['dirname'],
                                        'base_path' => $check_exist['path'],
                                        'base_name' => $check_exist['basename'],
                                        'created_at' => date("Y-m-d H:i:s"),
                                        'updated_at' => date("Y-m-d H:i:s")
                                    ];
                                } else { // nếu chưa tồn tại dir trên google driver
                                    try {
                                        $new_dir = createDirFullInfo($product_code, $item['dir_path']);
                                        $data_product_code = [
                                            'product_code' => $product_code,
                                            'parent_path' => $new_dir['dirname'],
                                            'base_path' => $new_dir['path'],
                                            'base_name' => $new_dir['basename'],
                                            'created_at' => date("Y-m-d H:i:s"),
                                            'updated_at' => date("Y-m-d H:i:s")
                                        ];
                                    } catch (\Exception $e) {
                                        logfile_system('-- Không thể tạo thư mục : '.$product_code.' trên driver vào thời điểm này');
                                    }
                                }
                                // nếu tồn tại data để lưu vào db
                                if(sizeof($data_product_code) > 0)
                                {
                                    $product_code_id = \DB::table('product_codes')->insertGetId($data_product_code);
                                    $lst_product_code[$product_code]['product_code_id'] = $product_code_id;
                                }
                            } else { // nếu đã từng tồn tại product code trong database
                                if(array_key_exists($product_code, $lst_product_code))
                                {
                                    $lst_product_code[$product_code]['product_code_id'] = $list_codes[$product_code];
                                }
                            }
                        }

                        // nếu tồn tại thông tin về thư mục trên google driver để lưu vào table product code
                        if (sizeof($lst_product_code) > 0)
                        {
                            foreach ($lst_product_code as $item)
                            {
                                \DB::table('designs')->whereIn('id',$item['design'])
                                    ->update(['product_code_id' => $item['product_code_id']]);
                            }
                            logfile_system('-- Chuẩn bị thành công product code id. Chuyển sang tạo mới thư mục SKU');
                        }
                    } else { // đã chuẩn bị xong product code id vào design. Kiểm tra xem đã tạo mới
                        logfile_system('-- Tạo mới thư mục SKU');
//                        print_r($list_working_files);
                        $working_file_update = array();
                        $working_file_error = array();

                        foreach ($list_working_files as $product_code_id => $info)
                        {
                            logfile_system('-- Đang thực hiện move '.sizeof($info['info']).' file.');
                            $parent_path = $info['path'];
                            foreach($info['info'] as $file)
                            {
                                $path = public_path($file['path'].$file['name']);
                                if (checkFileExist($file['name'], $file['working_base_dirname'])) {
                                    logfile_system('--- Đang move file : '.$file['name']);
                                    try {
                                        $move = Storage::cloud()->move($file['working_base_dirname'].'/'.$file['working_base_name'], $parent_path . '/' . $file['name']);
                                        if ($move)
                                        {
                                            $result = true;
                                        } else {
                                            $result = false;
                                        }
                                    } catch (\Exception $e) {
                                        $result = false;
                                        logfile_system(' -- Xảy ra lỗi nội bộ : '.$e->getMessage());
                                    }
                                    if ($result) {
                                        // tao data working file google driver cap nhat vao database
//                                        $working_file_update[$file['working_file_id']] = [
//                                            'base_name' => $file['working_base_name'],
//                                            'base_path' => $parent_path . '/'.$file['working_base_name'],
//                                            'base_dirname' => $parent_path,
//                                            'status' => 33,
//                                            'updated_at' => date("Y-m-d H:i:s")
//                                        ];
                                        $update = [
                                            'base_name' => $file['working_base_name'],
                                            'base_path' => $parent_path . '/'.$file['working_base_name'],
                                            'base_dirname' => $parent_path,
                                            'status' => 33,
                                            'updated_at' => date("Y-m-d H:i:s")
                                        ];
                                        \DB::table('working_files')->where('id',$file['working_file_id'])->update($update);
                                        logfile_system('--- Move thành công '.$file['name'].' lên google driver');
                                    } else {
                                        $working_file_error[] = $file['working_file_id'];
                                        logfile_system('--- Move thất bại '.$file['name'].' lên google driver');
                                    }
                                }
                            }
                        }
                        if(sizeof($working_file_update) > 0)
                        {
                            foreach ($working_file_update as $working_file_id => $data_update)
                            {
                                \DB::table('working_files')->where('id',$working_file_id)->update($data_update);
                            }
                            logfile_system('-- Move thành công '.sizeof($working_file_update).' files lên google driver');
                        }
                        if (sizeof($working_file_error) > 0)
                        {
                            logfile_system(' -- Xảy ra '.sizeof($working_file_error).' job bị lỗi. Không thể up lên google driver');
                            $update = [
                                'status' => env('STATUS_WORKING_ERROR'),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            \DB::table('working_files')->whereIn('id',$working_file_error)->update($update);
                        }
                    }
                } else {
                    logfile_system('-- Đã hết job hoàn thành cần move sang google driver thư mục. Chuyển sang công việc tiếp theo');
                    $return = true;
                }
            } else {
                logfile_system('-- Không thể lấy được data path để gửi file lên. Kiểm tra lại');
                $return = true;
            }
        } else {
            $return = true;
            logfile_system('-- Chưa tồn tại categories nào. Mời bạn tạo mới để fulfills');
        }
        return $return;
    }

    public function getFileFulfill()
    {
        echo "<pre>";
        $return = false;
        logfile_system("====== Tải file fulfill về local ======================");
        // lấy toàn bộ danh sách woo orders
        $file_fufills = \DB::table('woo_orders')
            ->leftjoin('designs', 'woo_orders.design_id', '=', 'designs.id')
            ->leftjoin('workings', 'workings.design_id', '=', 'designs.id')
            ->leftjoin('working_files as wf', 'workings.id', '=', 'wf.working_id')
            ->leftjoin('tool_categories as category','category.id', '=', 'designs.tool_category_id')
            ->select(
                'woo_orders.id','woo_orders.number', 'woo_orders.sku',
                'wf.id as working_file_id', 'wf.is_mockup',
                'wf.name', 'wf.path', 'wf.base_name', 'wf.base_path', 'wf.base_dirname',
                'category.type_fulfill_id', 'category.exclude_text', 'category.id as tool_category_id'
            )
            ->where('woo_orders.status', env('STATUS_WORKING_NEW'))
            ->whereIn('woo_orders.order_status', order_status())
            ->where('wf.is_mockup',0)
            ->limit(env('GOOGLE_LIMIT_UPLOAD_FILE'))
            ->orderBy('woo_orders.id')
            ->get()->toArray();
        if (sizeof($file_fufills) > 0)
        {
            logfile_system('-- Bắt đầu tải '.sizeof($file_fufills).' file về local để fulfill');
            $dt_insert_file_fulfill = array();
            $name_dirfulfill = 'file_fulfill';
            $dir_fulfill = public_path($name_dirfulfill);
            // tạo thư mục fulfill
            if (!File::exists($dir_fulfill)) {
                File::makeDirectory($dir_fulfill, $mode = 0777, true, true);
            }
            $working_file_error = array();
            $data_file_fulfill = array();
            $woo_order_update = array();
            foreach ($file_fufills as $file)
            {
                print_r($file);
                $extension = pathinfo($file->name)['extension'];
                $new_name = $file->number.'_'.$file->working_file_id.'.'.$extension;
                $destinationPath = $dir_fulfill.'/'.$file->number.'/'.$new_name;
                $name_destinationPath = $name_dirfulfill.'/'.$file->number.'/'.$new_name;
                logfile_system('--- Đang tải file: '.$new_name.' về local');
                // nếu chưa up lên google driver
                if ($file->base_name == '')
                {
                    $path = public_path($file->path.$file->name);
                    if(\File::exists($path))
                    {
                        logfile_system('--- Tồn tại file : '.$file->name.' trên local');
                        if (!\File::exists(dirname($destinationPath))) {
                            \File::makeDirectory(dirname($destinationPath), $mode = 0777, true, true);
                        }
                        $result = \File::copy($path,$destinationPath);
                        if ($result)
                        {
                            $data_file_fulfill[] = [
                                'order_number' => $file->number,
                                'woo_order_id' => $file->id,
                                'working_file_id' => $file->working_file_id,
                                'tool_category_id' => $file->tool_category_id,
                                'path' => $destinationPath,
                                'web_path_file' => $name_destinationPath,
                                'web_path_folder' => dirname($name_destinationPath),
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            $woo_order_update[] = $file->id;
                            logfile_system('--- Tải thành công job '.$new_name.' về local.');
                        } else {
                            logfile_system('--- Không thể tải job '.$new_name.' về local. Thử lại lần sau');
                        }
                    } else {
                        logfile_system('--- Không tồn tại file : '.$file->name.' trên local');
                        $working_file_error[] = $file->working_file_id;
                    }
                } else { // nếu đã up lên google driver rồi
//                    $check_exist_before = checkFileExistByBaseName($file->name, $file->base_dirname);
                    $check_exist_before = true;
                    if ($check_exist_before) {
                        if (!\File::exists(dirname($destinationPath))) {
                            \File::makeDirectory(dirname($destinationPath), $mode = 0777, true, true);
                        }
                        try {
                            $rawData = \Storage::cloud()->get($file->base_path);
                            $result = \Storage::disk('public_local')->put($name_destinationPath, $rawData);
                        } catch (\Exception $e) {
                            $result = false;
                        }
                        if ($result)
                        {
                            $path = $dir_fulfill.'/'.$new_name;
                            $data_file_fulfill[] = [
                                'order_number' => $file->number,
                                'woo_order_id' => $file->id,
                                'working_file_id' => $file->working_file_id,
                                'tool_category_id' => $file->tool_category_id,
                                'path' => $destinationPath,
                                'web_path_file' => $name_destinationPath,
                                'web_path_folder' => dirname($name_destinationPath),
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            $woo_order_update[] = $file->id;
                        } else {
                            logfile_system('--- Không thể tải job '.$new_name.' về local. Thử lại lần sau');
                        }
                    } else {
                        logfile_system('--- Không tồn tại file : '.$file->name.' trên google driver');
                        $working_file_error[] = $file->working_file_id;
                    }
                }
            }
            //nếu tải về được hết
            if (sizeof($data_file_fulfill) > 0)
            {
                \DB::table('file_fulfills')->insert($data_file_fulfill);
                $update_woo_order = [
                    'status' => env('STATUS_WORKING_DONE'),
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                \DB::table('woo_orders')->whereIn('id',$woo_order_update)->update($update_woo_order);
            }
            // nếu tồn tại working file bị lỗi.
            if (sizeof($working_file_error) > 0)
            {
                $update = [
                    'status' => env('STATUS_WORKING_ERROR'),
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                \DB::table('working_files')->whereIn('id', $working_file_error)->update($update);
            }
        } else {
            logfile_system('-- Đã hết file để tải về local. Chuyển sang công việc khác.');
            $return = true;
        }
        return $return;
    }
}
