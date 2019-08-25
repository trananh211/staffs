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
                        if ($list['fulfill_status'] == env('STATUS_NOTFULFILL')) continue;
                        if (in_array($list['woo_order_id'], $check_again)) continue;
                        $check_again[] = $list['woo_order_id'];
                        logfile('-- Đơn hàng ' . $list['number'] . ' chưa thanh toán tiền');
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
                    logfile('-- Fulfill file excel thành công của supplier:' . $list_orders['supplier_name'] . ' số lượng: ' . sizeof($dt));
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
        $lists = \DB::table('workings')
            ->join('woo_orders as wod', 'workings.woo_order_id', '=', 'wod.id')
            ->join('working_files as file', 'workings.id', '=', 'file.working_id')
            ->leftjoin('woo_product_drivers as wpd_goog', function ($join) {
                $join->on('wod.product_id', '=', 'wpd_goog.woo_product_id');
                $join->on('wod.woo_info_id', '=', 'wpd_goog.store_id');
            })
            ->select(
                'workings.id as working_id',
                'wod.id as woo_order_id', 'wod.sku', 'wod.woo_info_id as store_id',
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
            $this->uploadProductAutoToDriver();
        }
    }

    public function uploadProductAutoToDriver()
    {
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
            logfile(' -- Đã hết sản phẩm auto để upload fullfill.');
        }
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
}
