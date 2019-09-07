<?php

namespace App;

use Automattic\WooCommerce\HttpClient\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use DB;
use File;
use Illuminate\Http\UploadFile;
use Carbon\Carbon;
Use App\Jobs\SendPostEmail;
use Image;

class Working extends Model
{
    public $timestamps = true;
    protected $table = 'workings';

    public function log($str)
    {
        \Log::info($str);
    }

    /*Hàm check Auth ajax*/
    private static function checkAuth()
    {
        if (!Auth::check()) {
            $return = [
                'status' => 'error-auth',
                'message' => 'Bạn cần tải lại trang'
            ];
            return json_encode($return);
        } else {
            return Auth::id();
        }
    }

    private static function getListStore()
    {
        return \DB::table('woo_infos')->select('id', 'name')->get();
    }

    private static function getListProduct()
    {
        return \DB::table('woo_products as wpd')
            ->join('woo_infos', 'wpd.woo_info_id', '=', 'woo_infos.id')
            ->select(
                'wpd.id', 'wpd.woo_info_id', 'wpd.product_id', 'wpd.name', 'wpd.permalink', 'wpd.image',
                'woo_infos.name as store_name', 'wpd.type'
            )
            ->orderBy('id', 'DESC')
            ->get();
    }

    /*DASHBOARD*/
    public function adminDashboard($data)
    {
        $list_order = $this->getListOrderOfMonth(30);
        return view('/admin/dashboard')
            ->with(compact('list_order', 'data'));
    }

    public function staffDashboard($data)
    {
        return view('/staff/dashboard')
            ->with(compact('data'));
    }

    public function qcDashboard($data)
    {
        return view('/staff/qc_dashboard')
            ->with(compact('data'));
    }

    private function getListOrderOfMonth($subday)
    {
        $dt = Carbon::now();
//        $end = Carbon::parse('2019-03-22 03:55:54');
//        $past   = $dt->subMonth();
//        $future = $dt->addMonth();
//
//        echo $end->diffInDays($dt);
//        echo $end->diffForHumans($dt);
//        echo $dt->subDays(10)->diffForHumans();     // 10 days ago
//        echo $dt->diffForHumans($past);             // 1 month ago
//        echo $dt->diffForHumans($future);           // 1 month before

        $now = Carbon::now()->subDays($subday)->toDateString();
        $where = [
            ['woo_orders.created_at', '>', "'" . $now . "'"],
        ];
        $lists = \DB::table('woo_orders')
            ->join('woo_infos', 'woo_orders.woo_info_id', '=', 'woo_infos.id')
            ->leftJoin('trackings as t', 'woo_orders.id', '=', 't.woo_order_id')
            ->leftJoin('workings', 'woo_orders.id', '=', 'workings.woo_order_id')
            ->select(
                'woo_orders.id', 'woo_orders.number', 'woo_orders.status', 'woo_orders.product_name',
                'woo_orders.quantity', 'woo_orders.price', 'woo_orders.created_at', 'woo_orders.payment_method',
                'woo_infos.name', 'woo_orders.order_status', 'woo_infos.email',
                'woo_orders.sku', 'woo_orders.variation_full_detail', 'woo_orders.variation_detail',
                't.tracking_number', 't.status as tracking_status', 'workings.id as working_id'
            )
            ->where($where)
            ->orderBy('woo_orders.id', 'DESC')
            ->get();
        return $lists;
    }
    /*END DASHBOARD*/

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
                'workings.id', 'workings.status', 'workings.updated_at', 'workings.woo_order_id',
                'workings.qc_id', 'workings.worker_id', 'workings.reason', 'workings.redo',
                'worker.id as worker_id', 'worker.name as worker_name',
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
                'workings.id', 'workings.status', 'workings.updated_at',
                'workings.qc_id', 'workings.worker_id', 'workings.woo_order_id', 'workings.reason', 'workings.redo',
                'worker.id as worker_id', 'worker.name as worker_name', 'qc.id as qc_id', 'qc.name as qc_name',
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
                    ->where('custom_status', '!=', env('STATUS_P_AUTO_PRODUCT'))
                    ->orderBy('id', 'ASC')
                    ->limit(env("STAFF_GET_JOB_LIMIT"))
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
                                ->update(['status' => env('STATUS_WORKING_CHECK')]);
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
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            //ham lọc file ảnh trước khi upload - sẽ move vào DIR_TMP trước tiên
            $tmp = $this->filterFileUpload($rq['files'], '-PID-');
            $message = $tmp['message'];
            $files = $tmp['files'];
            $img = '';
            if (sizeof($files) > 0) {
                /*Kiểm tra file đang làm việc*/
                $lsts = \DB::table('workings')->select('id', 'number')
                    ->where([
                        'status' => env('STATUS_WORKING_NEW'),
                        'worker_id' => $uid
                    ])->get()->toArray();
                if (sizeof($lsts) > 0) {
                    $ar_filecheck = array();
                    foreach ($lsts as $lst) {
                        $ar_filecheck[$lst->number][] = $lst->id;
                    }
                    $mockup = array();
                    $file_upload = array();
                    /*Bắt đầu lọc file design up lên*/
                    foreach ($files as $file) {
                        $temp = explode('-PID-', $file);
                        $file_key = $temp[0];
                        $file_id = (int)$temp[1];
                        $file_id_text = $temp[1];
                        /*Nếu định dạng file trả về đúng kiểu*/
                        if (array_key_exists($file_key, $ar_filecheck) && in_array($file_id, $ar_filecheck[$file_key])) {
                            if (strpos(strtolower($file_id_text), 'mockup') !== false) {
                                $mockup[] = $file_id;
                            }
                            $file_upload[$file_id][] = $file;
                        } else {
                            $message .= getErrorMessage('File ' . $file . ': Bạn không làm job này.');
                        }
                    }
                    $deleted = array();
                    $db_new_working_files = array();
                    /*Bắt đầu upload file và delete file*/
                    foreach ($file_upload as $key_id => $f_up) {
                        /*Nếu tồn tại file mockup*/
                        if (in_array($key_id, $mockup)) {
                            foreach ($f_up as $f) {
                                if (\File::move(env('DIR_TMP') . $f, env('DIR_CHECK') . $f)) {
                                    $result = genThumb($f, env('DIR_CHECK') . $f, env('THUMB'));
                                    $thumb = '';
                                    if ($result) {
                                        $thumb = $result;
                                    }
                                    $db_new_working_files[] = [
                                        'name' => $f,
                                        'path' => env('DIR_CHECK'),
                                        'thumb' => $thumb,
                                        'worker_id' => $uid,
                                        'working_id' => $key_id,
                                        'is_mockup' => (strpos(strtolower($f), 'mockup') !== false) ? 1 : 0,
                                        'status' => env('STATUS_WORKING_CHECK'),
                                        'created_at' => date("Y-m-d H:i:s"),
                                        'updated_at' => date("Y-m-d H:i:s")
                                    ];
                                    $message .= getSuccessMessage('File ' . $f . ' tải lên thành công');
                                    $img .= thumb_c(env('APP_URL') . env('DIR_THUMB') . 'thumb_' . $f, 50, $f);
                                } else {
                                    $message .= getErrorMessage('File ' . $f . ' không thể tải lên lúc này. Mời thử lại');
                                }
                            }
                        } else {
                            $deleted = array_merge($deleted, $f_up);
                        }
                    }
                    if (sizeof($db_new_working_files) > 0) {
                        \DB::table('workings')->whereIn('id', $mockup)
                            ->update([
                                'status' => env('STATUS_WORKING_CHECK'),
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                        \DB::table('working_files')->insert($db_new_working_files);
                    }
                    if (sizeof($deleted) > 0) {
                        \File::delete($deleted);
                    }
                } else {
                    $message .= getErrorMessage('Hiện tại bạn không có job. Bạn làm sai quy trình.');
                }
            }
            return response()->json([
                'message' => getMessage($message),
                'uploaded_image' => $img
            ]);
        }
    }

    public function doNewIdea()
    {
        $uid = Auth::id();
        $users = \DB::table('users')->pluck('name', 'id')->toArray();
        $where = [
            ['ideas.worker_id', '=', $uid],
            ['ideas.status', '=', env('STATUS_WORKING_NEW')],
        ];
        $lists = $this->getListIdea($where);
        $now = date("Y-m-d H:i:s");
        $data = infoShop();
        return view('staff/new_idea', compact('lists', 'users', 'now', 'data'));
    }

    public function uploadIdea($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            //ham lọc file ảnh trước khi upload - sẽ move vào DIR_TMP trước tiên
            $tmp = $this->filterFileUpload($rq['files'], '-PID-');
            $message = $tmp['message'];
            $files = $tmp['files'];
            $img = '';
            if (sizeof($files) > 0) {
                $ideas_exist = DB::table('ideas')
                    ->where([
                        ['worker_id', '=', $uid],
                        ['status', '=', env('STATUS_WORKING_NEW')]
                    ])
                    ->pluck('id')
                    ->toArray();
                $db_files = array();
                $db_update_ideas = array();
                foreach ($files as $file) {
                    $idea_id = (int)explode('-PID-', $file)[1];
                    $before = explode('-PID-', $file)[0];
                    if (in_array($idea_id, $ideas_exist) && strpos(strtolower($file), 'idea') !== false) {
                        if (File::move(env('DIR_TMP') . $file, env('DIR_CHECK') . $file)) {
                            $db_files[] = [
                                'name' => $file,
                                'path' => env('DIR_CHECK') . $file,
                                'idea_id' => $idea_id,
                                'worker_id' => $uid,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            $db_update_ideas[] = $idea_id;
                            $message .= getSuccessMessage('Trả ' . $file . ' hàng thành công.');
                            $img .= thumb(env('DIR_NEW') . $file, 50, $file);
                        } else {
                            $message .= getErrorMessage('File ' . $file . ' không thể trả vào lúc này. Vui lòng thử lại');
                        }
                    } else {
                        File::delete(env('DIR_TMP') . $file);
                        $message .= getErrorMessage('File ' . $file . ' không phải công việc bạn đang làm.');
                    }
                }
                if (sizeof($db_files) > 0) {
                    \DB::beginTransaction();
                    try {
                        \DB::table('idea_files')->insert($db_files);
                        \DB::table('ideas')
                            ->whereIn('id', $db_update_ideas)
                            ->update(['status' => env('STATUS_WORKING_CHECK')]);
                        \DB::commit(); // if there was no errors, your query will be executed
                    } catch (\Exception $e) {
                        \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
                    }
                }
            }
            return response()->json([
                'message' => getMessage($message),
                'img' => $img
            ]);
        }
    }
    /*Staff*/

    /*Admin + QC*/
    /*
     *  Idea Job
     * */
    public function saveNewJob($request)
    {
        $uid = $this->checkAuth();
        $message = '';
        $img = '';
        if ($uid) {
            $rq = $request->all();
            $title = $rq['title'];
            $require = htmlentities(str_replace("\n", "<br />", $rq['require']));
            $worker_id = $rq['worker'];
            //ham lọc file ảnh trước khi upload
            $tmp = $this->filterFileUpload($rq['files'], '');
            $message .= $tmp['message'];
            $files = $tmp['files'];
            if (sizeof($files) > 0) {
                /*Kiểm tra tồn tại của file trước đó*/
                $files_existed = \DB::table('ideas')->pluck('name')->toArray();
                $db = array();
                $delete_file = array();
                foreach ($files as $file) {
                    if (in_array($file, $files_existed)) {
                        $message .= getErrorMessage('Đã tồn tại :' . $file . ' trước đó. 
                        Kiểm tra lại hoặc đổi tên file nhé.');
                        $delete_file[] = env('DIR_TMP') . $file;
                        continue;
                    }
                    File::move(env('DIR_TMP') . $file, env('DIR_NEW') . $file);
                    $db[] = [
                        'name' => $file,
                        'title' => $title,
                        'path' => env('DIR_NEW') . $file,
                        'require' => $require,
                        'qc_id' => $uid,
                        'status' => env('STATUS_WORKING_NEW'),
                        'worker_id' => $worker_id,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];

                    $message .= getSuccessMessage('Tạo job thành công : ' . $file);
                    $img .= thumb(env('DIR_NEW') . $file, 50, $file);
                }
                if (sizeof($delete_file) > 0) {
                    File::delete($delete_file);
                }
                if (sizeof($db) > 0) {
                    \DB::beginTransaction();
                    try {
                        \DB::table('ideas')->insert($db);
                        \DB::commit(); // if there was no errors, your query will be executed
                    } catch (\Exception $e) {
                        \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
                    }
                } else {
                    $message .= getErrorMessage('Xảy ra lỗi!. Tải lại trang và gửi hàng lại');
                }
            } else {
                $message = getErrorMessage('Bạn tải lên không có file nào là file ảnh. Bạn làm sai quy trình');
            }
        }

        return response()->json([
            'message' => (strlen(trim($message)) > 0) ? getMessage($message) : $message,
            'img' => $img
        ]);
    }

    public function listIdea()
    {
        $where = [
            ['ideas.status', '>=', env('STATUS_WORKING_NEW')],
            ['ideas.status', '<=', env('STATUS_WORKING_CHECK')],
//            ['created_at', '<=', $now],
//            ['created_at', '>=', "'" . $past . "'"],
        ];
        $tmp = $this->getListIdea($where);
        $list_ideas = $this->filterListIdea($tmp);
        $lists = $list_ideas['lists'];
        $idea_files = $list_ideas['idea_files'];
        $data = infoShop();
        return view('admin/list_idea', compact('lists', 'idea_files', 'data'));
    }

    public function listIdeaDone()
    {
        $where = [
            ['ideas.status', '=', env('STATUS_WORKING_CUSTOMER')],
//            ['created_at', '<=', $now],
//            ['created_at', '>=', "'" . $past . "'"],
        ];
        $tmp = $this->getListIdea($where);
        $list_ideas = $this->filterListIdea($tmp);
        $lists = $list_ideas['lists'];
        $idea_files = $list_ideas['idea_files'];
        $data = infoShop();
        return view('admin/list_idea_done', compact('lists', 'idea_files', 'data'));
    }

    private static function filterListIdea($tmp)
    {
        $users = \DB::table('users')->pluck('name', 'id')->toArray();
        $lists = array();
        $idea_files = array();
        if (sizeof($tmp) > 0) {
            $now = Carbon::now();
            foreach ($tmp as $idea) {
                if (strlen($idea->idea_files_name) > 0) {
                    $idea_files[$idea->idea_id][] = [
                        'idea_files_name' => $idea->idea_files_name,
                        'idea_files_path' => $idea->idea_files_path
                    ];
                }
                if (array_key_exists($idea->idea_id, $lists)) {
                    continue;
                }
                $created = new Carbon($idea->updated_at);
                $lists[$idea->idea_id] = [
                    'id' => $idea->idea_id,
                    'name' => $idea->ideas_name,
                    'title' => $idea->title,
                    'path' => $idea->ideas_path,
                    'require' => $idea->require,
                    'worker' => (array_key_exists($idea->worker_id, $users)) ? $users[$idea->worker_id] : '',
                    'qc' => (array_key_exists($idea->qc_id, $users)) ? $users[$idea->qc_id] : '',
                    'status' => $idea->ideas_status,
                    'redo' => $idea->redo,
                    'reason' => $idea->reason,
                    'updated_at' => $idea->updated_at,
                    'date' => compareTime($idea->updated_at, date("Y-m-d H:i:s"))
                ];
            }
        }
        return [
            'lists' => $lists, 'idea_files' => $idea_files
        ];
    }

    private static function getListIdea($where)
    {
        $lists = \DB::table('ideas')
            ->leftjoin('idea_files', 'ideas.id', '=', 'idea_files.idea_id')
            ->select(
                'ideas.id as idea_id', 'ideas.name as ideas_name', 'ideas.title', 'ideas.path as ideas_path',
                'ideas.require', 'ideas.worker_id', 'ideas.qc_id', 'ideas.status as ideas_status', 'ideas.redo',
                'ideas.reason', 'ideas.updated_at',
                'idea_files.name as idea_files_name', 'idea_files.path as idea_files_path'
            )
            ->where($where)
            ->orderBy('ideas.updated_at', 'ASC')
            ->get()
            ->toArray();
        return $lists;
    }

    /*
     * Return : (string) message + (array) files[]
     * */
    private function filterFileUpload($files, $str_compare)
    {
        $ext = ['jpg', 'jpeg', 'png'];
        $message = '';
        $paths = array(
            env('DIR_TMP'),
            env('DIR_NEW'),
            env('DIR_WORKING'),
            env('DIR_CHECK'),
            env('DIR_THUMB'));
        foreach ($paths as $path) {
            if (!File::exists(public_path($path))) {
                File::makeDirectory(public_path($path), $mode = 0777, true, true);
            }
        }
        $filter_files = array();
        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = $file->getClientOriginalName();
            if ($file->getSize() <= 10000000) {
                if (in_array(strtolower($extension), $ext)) {
                    if (strlen($str_compare) > 0 && strpos($filename, $str_compare) === false) {
                        $message .= getErrorMessage('File ' . $filename . ' sai định dạng tên. Mời đổi lại tên.');
                        continue;
                    }
                    if ($file->move(public_path(env('DIR_TMP')), $filename)) {
                        $filter_files[] = $filename;
                    } else {
                        $message .= getErrorMessage('Upload lỗi file :' . $filename . '. Làm ơn thử lại nhé.');
                    }
                } else {
                    $message .= getErrorMessage('File ' . $filename . ' không phải là file ảnh');
                }
            } else {
                $message .= getErrorMessage('File ' . $filename . ' lớn hơn 10MB');
            }
        }
        return array('message' => $message, 'files' => $filter_files);
    }

    public function autoGenThumb()
    {
        $files = \DB::table('working_files')
            ->select('id', 'path', 'name', 'thumb')
            ->where('status', '<', env('STATUS_WORKING_MOVE'))
            ->whereNull('thumb')
            ->get();
        if (sizeof($files) > 0) {
            foreach ($files as $file) {
                $path = $file->path . $file->name;
                if (\File::exists($path)) {
                    if (\File::copy($path, env("DIR_THUMB") . 'thumb_' . $file->name)) {
                        $thumb = genThumb($file->name, $path, env('THUMB'));
                        \DB::table('working_files')->where('id', $file->id)->update(['thumb' => $thumb]);
                    }
                }
            }
        }
    }

    public function checking()
    {
        $where = [
            ['workings.status', '=', env('STATUS_WORKING_CHECK')]
        ];
        $lists = $this->orderStaff($where);

        $where_working_file = [
            ['working_files.status', '=', env('STATUS_WORKING_CHECK')]
        ];
        $images = $this->getWorkingFile($where_working_file);
        $data = infoShop();
        return view('admin/checking')->with(compact('lists', 'images', 'data'));
    }

    public function working()
    {
        $where = [
            ['workings.status', '=', env('STATUS_WORKING_NEW')]
        ];
        $lists = $this->orderStaff($where);
        $data = infoShop();
        return view('admin/working')->with(compact('data', 'lists'));
    }

    private function getWorkingFile($where)
    {
        $return = array();
        $files = \DB::table('working_files')->select('working_id', 'name', 'thumb')
            ->where($where)
            ->get();
        if (sizeof($files) > 0) {
            $return = array();
            foreach ($files as $key => $file) {
                $return[$file->working_id][$key]['name'] = $file->name;
                $return[$file->working_id][$key]['thumb'] = $file->thumb;
            }
        }
        return $return;
    }

    public function sendCustomer($order_id)
    {
        /*Move file về thư mục done*/
        $where = [
            ['workings.id', '=', $order_id],
            ['wfl.is_mockup', '=', 1],
        ];
        $working = \DB::table('workings')
            ->join('working_files as wfl', 'workings.id', '=', 'wfl.working_id')
            ->join('woo_orders as wod', 'workings.woo_order_id', '=', 'wod.id')
            ->select(
                'workings.id', 'workings.number', 'workings.status', 'workings.woo_order_id', 'workings.woo_info_id',
                'wfl.path', 'wfl.name', 'wod.email as customer_email', 'wod.fullname as customer_name'
            )
            ->where($where)
            ->first();
        if ($working !== NULL) {
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
            \DB::table('working_files')->where('working_id', $order_id)
                ->update([
                    'status' => env('STATUS_WORKING_CUSTOMER'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            /*Todo: Xây dựng hàm gửi email tới khách hàng ở đây */
            $info = \DB::table('woo_infos')
                ->select('name', 'email', 'password', 'host', 'port', 'security')
                ->where('id', $working->woo_info_id)
                ->first();
            $title = '[ ' . $info->name . ' ] Update information about order ' . $working->number;
            $file = public_path($working->path . $working->name);
            $body = "Dear " . $working->customer_name . ",
We send you this email with information about " . $working->number . " order. 
We send detailed information about the design in the attached file below. 
If you want to resubmit your order redesign request, please reply to the message within 24 hours from the time you receive this email, after 24 hours we will move on to the next stage. 
If you are satisfied with the product, please do not reply to this email.
Thank you for your purchase at our store. Wish you a good day and lots of luck.
            ";
            $info->email_to = $working->customer_email;
            $info->title = $title;
            $info->body = $body;
            $info->file = $file;
            dispatch(new SendPostEmail($info));
            /*End todo: Xây dựng hàm gửi email */
            $status = 'success';
            $message = "Thành công. Tiếp tục kiểm tra các đơn hàng còn lại.";
        } else {
            $status = 'error';
            $message = "Xảy ra lỗi. Mời bạn thử lại. Nếu vẫn không được hãy báo với quản lý của bạn và kiểm tra đơn kế tiếp";
        }
        \Session::flash($status, $message);
        return back();
    }

    public function axReSendEmail($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            $working_id = $rq['working_id'];
            $order_id = $rq['order_id'];

            $where = [
                ['workings.id', '=', $working_id],
                ['wfl.is_mockup', '=', 1],
            ];
            $working = \DB::table('workings')
                ->join('working_files as wfl', 'workings.id', '=', 'wfl.working_id')
                ->join('woo_orders as wod', 'workings.woo_order_id', '=', 'wod.id')
                ->select(
                    'workings.id', 'workings.number', 'workings.status', 'workings.woo_order_id', 'workings.woo_info_id',
                    'wfl.path', 'wfl.name', 'wod.email as customer_email', 'wod.fullname as customer_name'
                )
                ->where($where)
                ->first();
            if ($working !== NULL) {
                /*Todo: Xây dựng hàm gửi email tới khách hàng ở đây */
                $info = \DB::table('woo_infos')
                    ->select('name', 'email', 'password', 'host', 'port', 'security')
                    ->where('id', $working->woo_info_id)
                    ->first();
                $title = '[ ' . $info->name . ' ] Update information about order ' . $working->number;
                $file = public_path($working->path . $working->name);
                $body = "Dear " . $working->customer_name . ",
We send you this email with information about " . $working->number . " order. 
We send detailed information about the design in the attached file below. 
If you want to resubmit your order redesign request, please reply to the message within 24 hours from the time you receive this email, after 24 hours we will move on to the next stage. 
If you are satisfied with the product, please do not reply to this email.
Thank you for your purchase at our store. Wish you a good day and lots of luck.
            ";
                $info->email_to = $working->customer_email;
                $info->title = $title;
                $info->body = $body;
                $info->file = $file;
                dispatch(new SendPostEmail($info));
                /*End todo: Xây dựng hàm gửi email */
                $status = 'success';
                $message = "Gửi lại email thành công. ";
            } else {
                $status = 'error';
                $message = "Xảy ra lỗi. Không thể tìm thấy file đang làm việc. Mời bạn thử lại. ";
            }

            return response()->json([
                'status' => $status,
                'message' => $message
            ]);
        }
    }

    public function redoDesigner($request)
    {
        try {
            $rq = $request->all();
            $reason = htmlentities(str_replace("\n", "<br />", trim($rq['reason'])));
            $update = [
                'status' => env('STATUS_WORKING_NEW'),
                'redo' => 1,
                'reason' => $reason,
                'updated_at' => date("Y-m-d H:i:s"),
            ];
            $files = \DB::table('working_files')
                ->select('name', 'path', 'thumb')
                ->where('working_id', $rq['order_id'])
                ->get();
            $deleted = array();
            foreach ($files as $file) {
                $deleted[] = public_path($file->path . $file->name);
                $deleted[] = public_path($file->thumb);
            }
            if (\File::delete($deleted)) {
                $status = 'success';
                $save = "Yêu cầu nhân viên làm lại thành công. Tiếp tục kiểm tra những đơn hàng còn lại.";
                \DB::table('workings')->where('id', $rq['order_id'])->update($update);
                \DB::table('working_files')->where('working_id', $rq['order_id'])->delete();
            } else {
                $status = 'error';
                $save = "[Redo] Yêu cầu nhân viên làm lại thất bại. Mời bạn thử lại";
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $status = 'error';
            $save = "[Redo Error] Xảy ra lỗi nội bộ. Mời bạn thử lại";
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        $this->log($save . "\n");
        \Session::flash($status, $save);
        return back();
    }

    /*phản hồi khách hàng*/
    public function reviewCustomer()
    {
        $where = [
            ['workings.status', '=', env('STATUS_WORKING_CUSTOMER')],
        ];
        $lists = $this->reviewWork($where);
        $where_working_file = [
            ['working_files.status', '=', env('STATUS_WORKING_CUSTOMER')],
            ['working_files.thumb', '!=', 'NULL']
        ];
        $images = $this->getWorkingFile($where_working_file);
        $data = infoShop();
        return view('/admin/review_customer', compact('lists', 'images', 'data'));
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
        if (Auth::check()) {
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
                \DB::table('working_files')->where('working_id', $working_id)->update($update);
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

    public function listWorker()
    {
        $list = \DB::table('users')->select('id', 'name')->where('level', env('WORKER'))->get();
        return $list;
    }

    public function axSkipProduct($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            $lst_products = $rq['list'];
            \DB::beginTransaction();
            try {
                \DB::table('workings')->whereIn('product_id', $lst_products)->delete();
                \DB::table('woo_orders')->whereIn('product_id', $lst_products)->update([
                    'status' => env('STATUS_SKIP'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                \DB::table('woo_products')->whereIn('product_id', $lst_products)->update([
                    'type' => getTypeProduct('App'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $status = 'success';
                $message = 'Chuyển đổi thành công.';
                \DB::commit(); // if there was no errors, your query will be executed
            } catch (\Exception $e) {
                $status = 'error';
                $message = 'Xảy ra lỗi. Mời bạn thử lại';
                \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            }
            return response()->json([
                'status' => $status,
                'message' => $message
            ]);
        }
    }

    public function axIdeaSendQc($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            $idea_id = $rq['idea_id'];

            \DB::beginTransaction();
            try {
                /*Lấy toàn bộ file Idea up lên google driver*/
                $lists = \DB::table('idea_files')
                    ->select('id', 'name', 'path', 'idea_id')
                    ->where('idea_id', $idea_id)->get();
                $db_google_files = array();
                foreach ($lists as $list) {
                    $path = upFile(public_path($list->path), env('GOOGLE_DRIVER_FOLDER_IDEA'));
                    if ($path) {
                        $db_google_files[] = [
                            'name' => $list->name,
                            'path' => $path,
                            'parent_path' => env('GOOGLE_DRIVER_FOLDER_IDEA'),
                            'idea_id' => $list->idea_id,
                            'idea_file_id' => $list->id,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                    }
                }
                if (sizeof($db_google_files) > 0) {
                    \DB::table('gg_files')->insert($db_google_files);
                    \DB::table('idea_files')->where('idea_id', $idea_id)
                        ->update([
                            'status' => env('STATUS_WORKING_CHECK'),
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                    \DB::table('ideas')->where('id', $idea_id)
                        ->update([
                            'qc_id' => $uid,
                            'status' => env('STATUS_WORKING_CUSTOMER'),
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                }
                $status = 'success';
                $message = 'Đã chuyển công việc upload sang cho bộ phận support. Tiếp tục công việc của bạn.';
                \DB::commit(); // if there was no errors, your query will be executed
            } catch (\Exception $e) {
                $status = 'error';
                $message = 'Lỗi. Không thể chuyển công việc upload sang cho bộ phận support. Hãy thử lại.';
                \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            }
            return response()->json([
                'status' => $status,
                'message' => $message
            ]);
        }
    }

    public function axRedoIdea($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            $idea_id = $rq['idea_id'];
            $reason = htmlentities(str_replace("\n", "<br />", trim($rq['reason'])));
            \DB::beginTransaction();
            try {
                $files = \DB::table('idea_files')->where('idea_id', $idea_id)->pluck('path');
                $delete_files = array();
                foreach ($files as $file) {
                    $delete_files[] = public_path() . '/' . $file;
                }
                File::delete($delete_files);
                \DB::table('idea_files')->where('idea_id', $idea_id)->delete();
                \DB::table('ideas')->where('id', $idea_id)->update([
                    'qc_id' => $uid,
                    'status' => env('STATUS_WORKING_NEW'),
                    'redo' => 1,
                    'reason' => $reason,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                $status = 'success';
                $message = 'Yêu cầu làm lại thành công. Tiếp tục công việc của bạn.';
                \DB::commit(); // if there was no errors, your query will be executed
            } catch (\Exception $e) {
                $status = 'error';
                $message = 'Lỗi. Không thể yêu cầu nhân viên làm lại lúc này. Hãy thử lại.';
                \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            }
            return response()->json([
                'status' => $status,
                'message' => $message
            ]);
        }
    }

    public function axUploadIdea($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            $idea_id = $rq['idea_id'];
            \DB::beginTransaction();
            try {
                \DB::table('idea_files')->where('idea_id', $idea_id)
                    ->update([
                        'status' => env('STATUS_WORKING_CUSTOMER'),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                \DB::table('ideas')->where('id', $idea_id)
                    ->update([
                        'qc_id' => $uid,
                        'status' => env('STATUS_WORKING_DONE'),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                $status = 'success';
                $message = 'Thành công. Tiếp tục công việc của bạn.';
                \DB::commit(); // if there was no errors, your query will be executed
            } catch (\Exception $e) {
                $status = 'error';
                $message = 'Xảy ra lỗi. Hãy thử lại.';
                \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            }
            return response()->json([
                'status' => $status,
                'message' => $message
            ]);
        }
    }

    public function axTakeJob($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            $working_id = $rq['working_id'];
            $woo_order_id = $rq['woo_order_id'];
            \DB::beginTransaction();
            try {
                \DB::table('workings')->where('id', $working_id)->delete();
                \DB::table('woo_orders')->where('id', $woo_order_id)
                    ->update([
                        'status' => env('STATUS_WORKING_NEW'),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                $status = 'success';
                $message = 'Trả Job thành công. Tiếp tục công việc của bạn.';
                \DB::commit(); // if there was no errors, your query will be executed
            } catch (\Exception $e) {
                $status = 'error';
                $message = 'Xảy ra lỗi. Hãy thử lại.';
                \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            }
            return response()->json([
                'status' => $status,
                'message' => $message
            ]);
        }
    }

    public function axDeleteLog($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            $name = storage_path() . '/logs/' . $rq['name'];
            $files = File::exists($name);
            if ($files) {
                File::delete($name);
                $status = 'success';
                $message = 'Xóa file ' . $rq['name'] . ' thành công';
            } else {
                $status = 'error';
                $message = 'Không xóa file ' . $rq['name'] . ' được do không tồn tại file. Mời bạn thử lại';
            }
            return response()->json([
                'status' => $status,
                'message' => $message
            ]);
        }
    }

    public function listAllOrder()
    {
        $data = infoShop();
        $list_order = $this->getListOrderOfMonth(30);
        $list_stores = $this->getListStore();
        return view('/admin/listorder')
            ->with(compact('list_order', 'data', 'list_stores'));
    }

    public function listAllProduct()
    {
        $data = infoShop();
        $list_products = $this->getListProduct();
        return view('/admin/list_product')
            ->with(compact('list_products', 'data'));
    }

    public function upDesignNormal($request)
    {
        $rq = $request->all();
        //ham lọc file ảnh trước khi upload - sẽ move vào DIR_TMP trước tiên
        $tmp = $this->filterFileUpload($rq['files'], '');
        $message = $tmp['message'];
        $files = $tmp['files'];
        $img = '';
        if (sizeof($files) > 0) {
            print_r($files);
        }
        return redirect('list-product')->with('success', getMessage($message));
    }

    /* Automatic create product*/
    public function viewCreateTemplate()
    {
        $data = infoShop();
        $stores = \DB::table('woo_infos')
            ->select('id', 'name', 'url', 'consumer_key', 'consumer_secret')
            ->get()->toArray();
        return view('/admin/woo/create_template')
            ->with(compact('data', 'stores'));
    }

    public function checkDriverProduct($request)
    {
        $rq = $request->all();
        $name = $rq['name'];
        $name_driver = trim($rq['name_driver']);
        $path_driver = env("GOOGLE_PRODUCTS") . '/' . trim($rq['path_driver']);
        $check_exist = \DB::table('woo_folder_drivers')
            ->where('path', $path_driver)
            ->first();
        if ($check_exist != NULL) {
            $message = 'Đã tồn tại thư mục "' . $name_driver . '" này rồi. Mời bạn thực hiện thư mục tiếp theo';
            return redirect('woo-create-template')->with('error', $message);
        } else {
            $check = checkDirExist($name_driver, $path_driver, env("GOOGLE_PRODUCTS"));
            if ($check) {
                $lists = scanGoogleDir($path_driver, 'dir');
                if ($lists) {
                    $data = array();
                    return view('/admin/woo/list_driver_name')
                        ->with(compact('lists', 'rq', 'data'));
                } else {
                    return redirect('woo-create-template')
                        ->with('error', 'Xảy ra lỗi quét thư mục Driver Google. Mời bạn thử lại.');
                }
            } else {
                return redirect('woo-create-template')
                    ->with('error', 'Không tồn tại thư mục driver "' . $name_driver . '" này. Mời bạn làm lại từ đầu.');
            }
        }
    }

    public function editWooTemplate($request)
    {
        try {
            $rq = $request->all();
            $message_status = 'error';
            $message = '';
            $product_name = ucwords(trim($rq['product_name']));
            $id = trim($rq['id']);
            $supplier_id = trim($rq['supplier_id']);
            $base_price = trim($rq['base_price']);
            $variation_change_id = trim($rq['variation_change_id']);
            $result = \DB::table('woo_templates')->where('id', $id)->update([
                'product_name' => $product_name,
                'supplier_id' => $supplier_id,
                'variation_change_id' => ($variation_change_id > 0) ? $variation_change_id : null,
                'base_price' => $base_price,
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            if ($result) {
                $message_status = 'success';
                $message = 'Cập nhật template thành công.';
            } else {
                $message = 'Cập nhật template thất bại. Mời bạn thử lại';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            echo $e->getMessage();
        }
        return redirect('woo-get-template')->with($message_status, $message);
    }

    public function saveCreateTemplate($request)
    {
        try {
            $rq = $request->all();
            $message_status = 'error';
            $message = '';
            $name_driver = trim($rq['name_driver']);
            $template_id = trim($rq['template_id']);
            $store_id = trim($rq['store_id']);
            $path_driver = env("GOOGLE_PRODUCTS") . '/' . trim($rq['path_driver']);
            $woo_folder_driver_id = \DB::table('woo_folder_drivers')->insertGetId([
                'name' => $name_driver,
                'path' => $path_driver,
                'template_id' => $template_id,
                'store_id' => $store_id,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            if ($woo_folder_driver_id) {
                $lists = scanGoogleDir($path_driver, 'dir');
                $woo_product_driver_data = array();
                if ($lists) {
                    foreach ($lists as $product) {
                        $woo_product_driver_data[] = [
                            'name' => strtolower($product['filename']),
                            'path' => $product['path'],
                            'template_id' => $template_id,
                            'store_id' => $store_id,
                            'woo_folder_driver_id' => $woo_folder_driver_id,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                    }
                    if (sizeof($woo_product_driver_data) > 0) {
                        \DB::table('woo_product_drivers')->insert($woo_product_driver_data);
                        $message_status = 'success';
                        $message = 'Lưu toàn bộ quá trình tạo template tự động thành công.';
                    }
                } else {
                    $message = 'Không quét được thư mục ' . $name_driver . ' trên google driver. Xin thử lại';
                }
            } else {
                $message = 'Không lưu được Folder ' . $name_driver . ' vào database. Xin thử lại';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            echo $e->getMessage();
        }
        return redirect('woo-processing-product')->with($message_status, $message);
    }

    public function processingProduct()
    {
        $data = array();
        $lists = \DB::table('woo_folder_drivers as wfd')
            ->join('woo_infos as wif', 'wfd.store_id', '=', 'wif.id')
            ->select(
                'wfd.id', 'wfd.name', 'wfd.status', 'wfd.updated_at',
                'wif.name as store_name'
            )
            ->whereDate('wfd.created_at', '>', Carbon::now()->subDays(30))
            ->orderBy('wfd.created_at', 'DESC')
            ->get()->toArray();
        $pro_upload = array();
        $pro_status = array();
        if (sizeof($lists) > 0) {
            $lst_products = array();
            foreach ($lists as $list) {
                $lst_products[] = $list->id;
            }
            if (sizeof($lst_products) > 0) {
                $lst_product_uploads = \DB::table('woo_product_drivers as wpd')
                    ->leftjoin('woo_image_uploads as wup', 'wpd.id', '=', 'wup.woo_product_driver_id')
                    ->selectRaw(
                        'wpd.id, wpd.name, wpd.woo_folder_driver_id, wpd.woo_product_name, wpd.woo_slug, wpd.status,
                         count(DISTINCT(wup.id)) as images'
                    )
                    ->whereIn('woo_folder_driver_id', $lst_products)
                    ->groupBy('wpd.id')
                    ->get()
                    ->toArray();
                $all = 0;
                foreach ($lst_product_uploads as $lst) {
                    if (!array_key_exists($lst->woo_folder_driver_id, $pro_status)) {
                        $uploading = 0;
                        $done = 0;
                        $all = 1;
                    } else {
                        $all += 1;
                    }
                    if ($lst->status == 1) {
                        $uploading += 1;
                    } else if ($lst->status == 3) {
                        $done += 1;
                    }
                    $pro_status[$lst->woo_folder_driver_id] = array(
                        'uploading' => $uploading,
                        'done' => $done,
                        'all' => $all
                    );
                    $pro_upload[$lst->woo_folder_driver_id][] = array(
                        'name' => $lst->name,
                        'woo_product_name' => $lst->woo_product_name,
                        'woo_slug' => $lst->woo_slug,
                        'status' => $lst->status,
                        'images' => $lst->images
                    );
                }
            }
        }
//        dd($pro_upload);
        return view('/admin/woo/processing_product')
            ->with(compact('lists', 'data', 'pro_upload', 'pro_status'));
    }

    public function addNewSupplier($request)
    {
        $rq = $request->all();
        $name = trim(ucwords(strtolower($rq['name'])));
        $note = trim($rq['note']);
        $status = trim($rq['status']);
        $alert = 'error';
        $check = \DB::table('suppliers')->where('name', $name)->first();
        if ($check == null) {
            //tạo mới thư mục trên driver
            $path = createDir($name, env('GOOGLE_SUP_FOLDER'));
            $result = \DB::table('suppliers')->insert([
                'name' => $name,
                'note' => htmlentities(trim($note)),
                'path' => $path,
                'status' => $status,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            $message = 'Tạo mới supplier thành công.';

            if ($result) {
                $alert = 'success';
            } else {
                $message = 'Xảy ra lỗi. Không thể tạo mới supplier lúc này. Xin mời bạn thử lại';
            }
        } else {
            if (isset($rq['id'])) {
                //cap nhat lai thong tin supplier
                $result = \DB::table('suppliers')->where('id', $rq['id'])->update([
                    'name' => $name,
                    'note' => htmlentities(trim($note)),
                    'status' => $status,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);

                if ($result) {
                    $alert = 'success';
                    $message = 'Cập nhật supplier thành công.';
                } else {
                    $message = 'Cập nhật supplier thất bại.';
                }
            } else {
                $message = 'Đã tồn tại supplier này rồi. Mời bạn kiểm tra lại nhé.';
            }
        }
        return redirect('woo-supplier')->with($alert, $message);
    }

    public function editSupplier($supplier_id)
    {
        $alert = 'error';
        $supplier = \DB::table('suppliers')
            ->select('id', 'name', 'note', 'status')->where('id', $supplier_id)->get()->toArray();
        if (sizeof($supplier) == 0) {
            $message = 'Không tồn tại supplier này. Mời bạn tạo mới lại.';
            return redirect('woo-supplier')->with($alert, $message);
        } else {
            $data = array();
            return view('/admin/woo/add_new_supplier', compact('data', 'supplier'));
        }
    }

    public function deleteSupplier($supplier_id)
    {
        try {
            $alert = 'error';
            $exists = \DB::table('suppliers')->where('id', $supplier_id)->first();
            if ($exists == null) {
                $message = 'Không tồn tại supplier này trên hệ thống.';
            } else {
                $check = $exists;
                $result = \DB::table('suppliers')->where('id', $supplier_id)->delete();
                if ($result) {
                    $r = deleteDir($check->name, dirname($check->path));
                    if ($r) {
                        $alert = 'success';
                        $message = 'Xóa thành công.';
                    } else {
                        $message = 'Xóa trong database thành công. Nhưng không thể xóa Driver Goolge. Mời bạn xóa tay';
                    }
                } else {
                    $message = ' Xảy ra lỗi không thể xóa supplier. Mời bạn thử lại sau';
                }
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            logfile($e->getMessage());
            echo $e->getMessage();
        }
        return redirect('woo-supplier')->with($alert, $message);
    }

    public function ajaxPutConvertVariation($request)
    {
        try {
            $uid = $this->checkAuth();
            $alert = 'error';
            $message = '';
            $url = '';
            if ($uid) {
                $rq = $request->all();
                $variation_name = trim(ucwords($rq['variation_name']));
                $variation_suplier = trim($rq['variation_suplier']);
                $check = \DB::table('variation_changes')
                    ->where('name', $variation_name)->where('suplier_id', $variation_suplier)
                    ->first();

                if ($check == null) {
                    $variation_change_id = \DB::table('variation_changes')->insertGetId([
                        'name' => $variation_name,
                        'suplier_id' => $variation_suplier,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    if ($variation_change_id) {
                        $json_data = $rq['json_data'];
                        $data = array();
                        foreach ($json_data as $val) {
                            $variation_old_slug = mb_ereg_replace("[.]", '-', $val['variation_old']);
                            $data[] = [
                                'variation_change_id' => $variation_change_id,
                                'variation_old' => trim($val['variation_old']),
                                'variation_compare' => trim($val['variation_compare']),
                                'variation_new' => trim($val['variation_new']),
                                'variation_sku' => trim($val['variation_sku']),
                                'variation_old_slug' => str_replace(" ", "-",
                                    mb_ereg_replace("([^\w\s\d\~,;\[\]\(\).-])", '', strtolower(trim($variation_old_slug)))),
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        }
                        $result = \DB::table('variation_change_items')->insert($data);
                        if ($result) {
                            $alert = 'success';
                            $message = 'Tạo thành công variation.';
                            $url = url('woo-list-convert-variation');
                        }
                    }
                } else {
                    $message = 'Đã tồn tại variation name này rồi. Mời bạn thử lại với tên khác';
                }
            } else {
                $message = 'Đã hết phiên login. Mời bạn tải lại trang và làm lại từ đầu.';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $alert = 'error';
            logfile($e->getMessage());
            $message = $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return response()->json([
            'message' => $message,
            'result' => $alert,
            'url' => $url
        ]);
    }

    public function deleteConvertVariation($id)
    {
        try {
            \DB::table('variation_changes')->where('id', $id)->delete();
            \DB::table('variation_change_items')->where('variation_change_id', $id)->delete();
            $alert = 'success';
            $message = 'Xóa thành công';
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $alert = 'error';
            logfile($e->getMessage());
            $message = 'Xóa thất bại. ' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return redirect('woo-list-convert-variation')->with($alert, $message);
    }

    public function ajaxCheckVariationExist($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            $variation_name = trim(ucwords($rq['variation_name']));
            $variation_suplier = trim($rq['variation_suplier']);
            $check = \DB::table('variation_changes')
                ->where('name', $variation_name)->where('suplier_id', $variation_suplier)
                ->first();
            if ($check != null) {
                $message = '<small class="red-text">Tên <b>' . $variation_name . '</b> đã tồn tại rồi. Mời bạn chọn tên khác.</small>';
                $alert = 'error';
            } else {
                $message = '<small class="green-text">Bạn có thể sử dụng tên <b>' . $variation_name . '</b> này.</small>';
                $alert = 'success';
            }
            return response()->json([
                'message' => $message,
                'result' => $alert
            ]);
        }
    }
    /*End Admin + QC*/
}
