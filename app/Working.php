<?php

namespace App;

use Automattic\WooCommerce\HttpClient\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use DB;
use File;
use Illuminate\Http\UploadFile;

class Working extends Model
{
    public $timestamps = true;
    protected $table = 'workings';

    public function log($str)
    {
        \Log::info($str);
    }

    /*Hàm hiển thị toàn bộ danh sách order ra ngoài màn hình nhân viên*/
    public function listOrder()
    {
        $uid = Auth::id();
        $where = [
            ['workings.worker_id', '=', $uid],
            ['workings.status', '=', env('STATUS_WORKING_NEW')],
        ];
        return $this->orderStaff($where);
    }

    public function detailOrder($order_id)
    {
        $where = [
            ['workings.id', '=', $order_id],
        ];
        return $this->orderStaff($where);
    }

    private static function orderStaff($where)
    {
        $lists = \DB::table('workings')
            ->join('woo_orders', 'workings.woo_order_id', '=', 'woo_orders.id')
            ->join('woo_products', 'workings.product_id', '=', 'woo_products.product_id')
            ->join('users as worker', 'workings.worker_id', '=', 'worker.id')
            ->select(
                'workings.id', 'workings.status', 'workings.updated_at', 'workings.filename',
                'workings.qc_id','workings.worker_id','workings.reason','workings.redo',
                'worker.id as worker_id','worker.name as worker_name',
                'woo_orders.number', 'woo_orders.detail',
                'woo_products.name', 'woo_products.permalink', 'woo_products.image'
            )
            ->where($where)
            ->orderBy('workings.id', 'ASC')
            ->get()
            ->toArray();
        return $lists;
    }

    private static function reviewWork($where)
    {
        $lists = \DB::table('workings')
            ->join('woo_orders', 'workings.woo_order_id', '=', 'woo_orders.id')
            ->join('woo_products', 'workings.product_id', '=', 'woo_products.product_id')
            ->join('users as worker', 'workings.worker_id', '=', 'worker.id')
            ->join('users as qc', 'workings.qc_id', '=', 'qc.id')
            ->select(
                'workings.id', 'workings.status', 'workings.updated_at', 'workings.filename',
                'workings.qc_id','workings.worker_id', 'workings.woo_order_id','workings.reason','workings.redo',
                'worker.id as worker_id','worker.name as worker_name','qc.id as qc_id','qc.name as qc_name',
                'woo_orders.number', 'woo_orders.detail',
                'woo_products.name', 'woo_products.permalink', 'woo_products.image'
            )
            ->where($where)
            ->orderBy('workings.id', 'ASC')
            ->get()
            ->toArray();
        return $lists;
    }

    /*Hàm nhận job sau khi ấn button ở trang staff*/
    public function staffGetJob()
    {
        $this->log('=============== GET NEW JOB=================');
        if (Auth::check()) {
            $uid = Auth::id();
            $check_working = \DB::table('workings')
                ->select('id')
                ->where([
                    ['worker_id', '=', $uid],
                    ['status', '=', env('STATUS_WORKING_NEW')]
                ])
                ->count();
            if ($check_working == 0) {
                $username = Auth::user()->original['name'];
                $this->log(' Nhân viên ' . $username . ' xin job mới.');
                $jobs = DB::table('woo_orders')
                    ->select('id', 'woo_info_id', 'order_id', 'product_id', 'number')
                    ->where('status', env('STATUS_WORKING_NEW'))
                    ->orderBy('id', 'ASC')
                    ->limit(2)
                    ->get()->toArray();
                if (sizeof($jobs) > 0) {
                    $db = array();
                    //gộp toàn bộ order vào cùng 1 job
                    foreach ($jobs as $order) {
                        $db[$order->order_id][] = [
                            'woo_info_id' => $order->woo_info_id,
                            'woocommerce_order_id' => $order->order_id,
                            'order_id' => $order->id,
                            'product_id' => $order->product_id,
                            'number' => $order->number,
                        ];
                    }
                    $i = 0;
                    $update_order = array();
                    //lấy job đầu tiên ra trả cho nhân viên
                    foreach ($db as $woo_order_id => $orders) {
                        if ($i > 3) break;
                        foreach ($orders as $value) {
                            $data[] = [
                                'woo_info_id' => $value['woo_info_id'],
                                'woo_order_id' => $value['order_id'],
                                'product_id' => $value['product_id'],
                                'number' => $value['number'],
                                'store_order_id' => $value['woocommerce_order_id'],
                                'worker_id' => $uid,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            $update_order[] = $value['order_id'];
                        }
                        $i++;
                    }
                    $update_order = array_unique($update_order);
                    if (sizeof($data) > 0) {
                        \DB::beginTransaction();
                        try {
                            \DB::table('workings')->insert($data);
                            \DB::table('woo_orders')
                                ->whereIn('id', $update_order)
                                ->update(['status' => env('STATUS_WORKING_NEW')]);
                            $return = true;
                            $save = "Chia " . sizeof($data) . " order cho '" . $username . "' thanh cong.";
                            \Session::flash('success', 'Nhận việc thành công. Vui lòng hoành thành sớm.');
                            \DB::commit(); // if there was no errors, your query will be executed
                        } catch (\Exception $e) {
                            $return = false;
                            $save = "Chia " . sizeof($data) . " order cho '" . $username . "' thất bại.";
                            \Session::flash('error', 'Xảy ra lỗi. Vui lòng thử lại!');
                            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
                        }
                        $this->log($save);
                    }
                } else {
                    \Session::flash('error', 'Đã hết công việc. Báo với quản lý của bạn để nhận việc mới.!');
                    $this->log("Đã hết order để chia cho nhân viên. \n");
                }
            } else {
                \Session::flash('error', 'Hãy hoàn thành hết công việc hiện tại trước đã nhé!');
            }
            return redirect('staff-dashboard');
        }
    }

    /*Hàm trả job của nhân viên*/
    public function staffUpload($request)
    {
        $rq = $request->all();
        $files = $rq['staff_done'];
        $ext = ['jpg', 'jpeg', 'png'];
        $return = array();
        $message = '';
        $uploaded_image = '';
        if ($request->hasfile('staff_done')) {
            $list_order_id = array(); /*liệt kê toàn bộ id order cho vào mảng để query*/
            $number_order_id = array(); /*nhóm các number id với 1 order id vào chung 1 mảng*/
            $array_filename = array(); /*nhóm các number id với filename vào chung 1 mảng giống với $number_order_id*/
            /*Validate định dạng file*/
            foreach ($files as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $filename = $file->getClientOriginalName();
                if (in_array(strtolower($extension), $ext)) {
                    if ($file->getSize() <= 10000000) {
                        /*Kiểm tra tên xem có đúng định dạng upload hay không*/
                        $name = pathinfo($filename, PATHINFO_FILENAME);
                        if (strpos($name, '-PID-') === false) {
                            $message .= '<li class="red lighten-3">File ' . $filename . ' sai định dạng tên hoặc -PID- cần viết hoa. Mời đổi lại tên. </li>';
                        } else {
                            /*Định dạng file: S247-USA-3156-PID-3.jpg*/
                            $split_name = explode('-PID-', $name);
                            $list_order_id[] = $split_name[1];
                            $number_order_id[$split_name[1]] = $split_name[0];
                            $array_filename[$split_name[1]] = $file;
                        }
                    } else {
                        echo $file->getSize() . "\n";
                        $message .= '<li class="red lighten-3">File ' . $filename . ' lớn hơn 10MB</li>';
                    }
                } else {
                    $message .= '<li class="red lighten-3">File ' . $filename . ' không phải là file ảnh</li>';
                }
            }
            /*tìm kiếm file có tồn tại trong Database hay không*/
            if (sizeof($list_order_id) > 0) {
                $lst = \DB::table('workings')
                    ->select('id', 'number', 'woo_order_id')
                    ->whereIn('id', $list_order_id)
                    ->get()->toArray();
                if (sizeof($lst) > 0) {
                    if (!File::exists(public_path(env('WORKING_DIR')))) {
                        File::makeDirectory(public_path(env('WORKING_DIR')), $mode = 0777, true, true);
                    }
//                    $db_update = array();
                    foreach ($lst as $item) {
                        if (isset($number_order_id[$item->id]) && $item->number == $number_order_id[$item->id]) {
                            $new_name = $array_filename[$item->id]->getClientOriginalName();
                            if ($array_filename[$item->id]->move(public_path(env('WORKING_DIR')), $new_name)) {
                                $message .= "<li class='green lighten-1'>Upload thành công file " . $new_name . "</li>";
                                $uploaded_image .= '<img src="' . env('WORKING_DIR') . '/' . $new_name . '" 
                                class="img-thumbnail" width="150" title="' . $new_name . '"/>';
                                $ud_working = \DB::table('workings')->where('id', $item->id)
                                    ->update([
                                        'filename' => $new_name,
                                        'status' => env('STATUS_WORKING_CHECK'),
                                        'updated_at' => date("Y-m-d H:i:s")
                                    ]);
                                if ($ud_working)
                                {
                                    $update_woo_order[] = $item->woo_order_id;
                                }
                            } else {
                                $message .= '<li class="red lighten-3">Upload lỗi file :' . $new_name . '. Làm ơn thử lại nhé.</li>';
                            }
                        } else {
                            $message .= "<li class='red lighten-3'>File " . $item->number . " không tồn tại trong hệ thống. Kiểm tra lại tên file.</li>";
                        }
                    }
                    if (sizeof($update_woo_order) > 0) {
                        \DB::table('woo_orders')->whereIn('id', $update_woo_order)
                            ->update([
                                'status' => env('STATUS_WORKING_CHECK'),
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                    }
                } else {
                    $message .= "<li class='red lighten-3' >File " . implode(',', $number_order_id) . " không tồn tại trong hệ thống. Kiểm tra lại tên file.</li>";
                }
            }
        }
        return response()->json([
            'message' => '<ul>' . $message . "</ul>",
            'uploaded_image' => $uploaded_image
        ]);
    }

    /*Admin + QC*/
    public function checking()
    {
        $where = [
            ['workings.status', '=', env('STATUS_WORKING_CHECK')]
        ];
        return $this->orderStaff($where);
    }

    public function sendCustomer($order_id)
    {
        \DB::beginTransaction();
        try {
            if (!File::exists(public_path(env('DONE_DIR')))) {
                File::makeDirectory(public_path(env('DONE_DIR')), $mode = 0777, true, true);
            }
            /*Move file về thư mục done*/
            $where = [
                ['id', '=', $order_id],
            ];
            $working = \DB::table('workings')
                ->select('id', 'filename', 'number', 'status', 'woo_order_id')
                ->where($where)
                ->first();
            if ($working !== NULL) {
                $path_file = public_path(env('WORKING_DIR')) . $working->filename;
                if (File::exists($path_file) && $working->status == env('STATUS_WORKING_CHECK')) {
                    if (File::move($path_file, public_path(env('DONE_DIR')) . $working->filename)) {
                        \DB::table('workings')->where('id', $order_id)
                            ->update([
                                'status' => env('STATUS_WORKING_CUSTOMER'),
                                'qc_id' => Auth::id(),
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                        \DB::table('woo_orders')->where('id', $working->woo_order_id)
                            ->update([
                                'status' => env('STATUS_WORKING_CUSTOMER'),
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                        /*Todo: Xây dựng hàm gửi email tới khách hàng ở đây */

                        /*End todo: Xây dựng hàm gửi email */
                        $status = 'success';
                        $message = "Thành công. Tiếp tục kiểm tra các đơn hàng còn lại.";
                    } else {
                        $status = 'error';
                        $message = "Xảy ra lỗi. Không thể chuyển file " . $working->filename . " sang thư mục sẵn sàng.";
                    }

                } else {
                    $status = 'error';
                    $message = "Xảy ra lỗi. File không tồn tại hoặc đã được kiểm tra trước đó rồi.";
                }
            } else {
                $status = 'error';
                $message = "Xảy ra lỗi. Mời bạn thử lại. Nếu vẫn không được hãy báo với quản lý của bạn và kiểm tra đơn kế tiếp";
            }
            \Session::flash($status, $message);
            $return = true;
            $save = "Move products to database and folder successfully";
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $return = false;
            $save = "[Error] Can't move product to database and folder.";
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        $this->log($save);
        return back();
    }

    public function redoDesigner($request)
    {
        $rq = $request->all();
        $update = [
            'status' => env('STATUS_WORKING_NEW'),
            'redo' => 1,
            'reason' => $rq['reason'],
            'updated_at' => date("Y-m-d H:i:s"),
        ];
        \DB::beginTransaction();
        try {
            \DB::table('workings')->where('id', $rq['order_id'])->update($update);
            $return = true;
            $save = "Yêu cầu nhân viên làm lại thành công. Tiếp tục kiểm tra những đơn hàng còn lại.";
            \Session::flash('success', $save);
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $return = false;
            $save = "Yêu cầu nhân viên làm lại thất bại. Mời bạn thử lại";
            \Session::flash('error', $save);
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        $this->log($save . "\n");
        return back();
    }

    /*phản hồi khách hàng*/
    public function reviewCustomer()
    {
        $where = [
            ['workings.status', '=', env('STATUS_WORKING_CUSTOMER')],
        ];
        return $this->reviewWork($where);
    }

    public function supplier()
    {
        $where = [
            ['workings.status', '=', env('STATUS_WORKING_DONE')],
        ];
        return $this->reviewWork($where);
    }

    public function eventQcDone($request)
    {
        if (Auth::check())
        {
            $working_id = $request->all()['working_id'];
            $order_id = $request->all()['order_id'];
            \DB::beginTransaction();
            try {
                $update = [
                    'status' => env('STATUS_WORKING_DONE'),
                    'updated_at' => date("Y-m-d H:i:s"),
                ];
                \DB::table('workings')->where('id', $working_id)->update($update);
                \DB::table('woo_orders')->where('id', $order_id)->update($update);
                $status = 'success';
                $message = "Yêu cầu chuyển cho supplier thành công. Tiếp tục kiểm tra các đơn hàng còn lại.";
                \DB::commit(); // if there was no errors, your query will be executed
            } catch (\Exception $e) {
                $status = 'error';
                $message = "Yêu cầu chưa được thực hiện. Vui lòng tải lại trang và tiếp tục với đơn khác nếu vẫn lỗi.";
                \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            }
            $response = [
                'status' => $status,
                'message' => $message,
            ];
            return json_encode($response);
        } else {
            $error = [
                'status' => 'error',
                'message' => 'Bạn đã quá thời gian đăng nhập. Bạn cần đăng nhập lại',
            ];
            return json_encode($error);
        }
    }
    /*End Admin + QC*/
}
