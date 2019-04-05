<?php

namespace App;

use Automattic\WooCommerce\HttpClient\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use DB;
use File;
use Illuminate\Http\UploadFile;
use Carbon\Carbon;

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

    private static function getMessage($message)
    {
        return '<ul class="collection">' . $message . '</ul>';
    }

    private static function getErrorMessage($message)
    {
        return '<li class="red lighten-3 collection-item">' . $message . '</li>';
    }

    private static function getSuccessMessage($message)
    {
        return '<li class="green lighten-1 collection-item">' . $message . '</li>';
    }

    /*DASHBOARD*/
    public function adminDashboard()
    {
        $new_order = $this->getNewOrder();
        $working_order = $this->getworkingOrder();
        $checking_order = $this->getCheckingOrder();
        $late_order = $this->getLateOrder();
        $list_order = $this->getListOrderOfMonth(30);
        return view('/admin/dashboard')
            ->with(compact('new_order', 'working_order', 'checking_order', 'late_order', 'list_order'));
    }

    public function staffDashboard()
    {
        $new_order = $this->getNewOrder();
        $working_order = $this->getworkingOrder();
        $checking_order = $this->getCheckingOrder();
        $late_order = $this->getLateOrder();
        return view('/staff/dashboard')
            ->with(compact('new_order', 'working_order', 'checking_order', 'late_order'));
    }

    public function qcDashboard()
    {
        $new_order = $this->getNewOrder();
        $working_order = $this->getworkingOrder();
        $checking_order = $this->getCheckingOrder();
        $late_order = $this->getLateOrder();
        return view('/staff/qc_dashboard')
            ->with(compact('new_order', 'working_order', 'checking_order', 'late_order'));;
    }

    private function getNewOrder()
    {
        return \DB::table('woo_orders')->where('status', env('STATUS_WORKING_NEW'))->count();
    }

    private function getworkingOrder()
    {
        return \DB::table('woo_orders')->where('status', env('STATUS_WORKING_CHECK'))->count();
    }

    private function getCheckingOrder()
    {
        return \DB::table('woo_orders')->where('status', env('STATUS_WORKING_CUSTOMER'))->count();
    }

    private static function getLateOrder()
    {
        $cur_date = Carbon::now();
        return \DB::table('woo_orders')
            ->where('status', '<>', env('STATUS_WORKING_DONE'))
            ->whereRaw("DATEDIFF('" . Carbon::now() . "',updated_at)  > 1")
            ->count();
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
            ->select(
                'woo_orders.id', 'woo_orders.number', 'woo_orders.status', 'woo_orders.product_name',
                'woo_orders.quantity', 'woo_orders.price', 'woo_orders.created_at', 'woo_orders.payment_method',
                'woo_infos.name'
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
                'workings.id', 'workings.status', 'workings.updated_at',
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
                    ->orderBy('id', 'ASC')
                    ->limit(5)
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
                            $message .= $this->getErrorMessage('File ' . $file . ': Bạn không làm job này.');
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
                                    $db_new_working_files[] = [
                                        'name' => $f,
                                        'path' => env('DIR_CHECK'),
                                        'worker_id' => $uid,
                                        'working_id' => $key_id,
                                        'is_mockup' => (strpos(strtolower($f), 'mockup') !== false) ? 1 : 0,
                                        'status' => env('STATUS_WORKING_CHECK'),
                                        'created_at' => date("Y-m-d H:i:s"),
                                        'updated_at' => date("Y-m-d H:i:s")
                                    ];
                                    $message .= $this->getSuccessMessage('File ' . $f . ' tải lên thành công');
                                    $img .= thumb(env('DIR_CHECK') . $f, 50, $f);
                                } else {
                                    $message .= $this->getErrorMessage('File ' . $f . ' không thể tải lên lúc này. Mời thử lại');
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
                    $message .= $this->getErrorMessage('Hiện tại bạn không có job. Bạn làm sai quy trình.');
                }
            }
            return response()->json([
                'message' => $this->getMessage($message),
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
        return view('staff/new_idea', compact('lists', 'users', 'now'));
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
                            $message .= $this->getSuccessMessage('Trả ' . $file . ' hàng thành công.');
                            $img .= thumb(env('DIR_NEW') . $file, 50, $file);
                        } else {
                            $message .= $this->getErrorMessage('File ' . $file . ' không thể trả vào lúc này. Vui lòng thử lại');
                        }
                    } else {
                        File::delete(env('DIR_TMP') . $file);
                        $message .= $this->getErrorMessage('File ' . $file . ' không phải công việc bạn đang làm.');
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
                'message' => $this->getMessage($message),
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
                        $message .= $this->getErrorMessage('Đã tồn tại :' . $file . ' trước đó. 
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

                    $message .= $this->getSuccessMessage('Tạo job thành công : ' . $file);
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
                    $message .= $this->getErrorMessage('Xảy ra lỗi!. Tải lại trang và gửi hàng lại');
                }
            } else {
                $message = $this->getErrorMessage('Bạn tải lên không có file nào là file ảnh. Bạn làm sai quy trình');
            }
        }

        return response()->json([
            'message' => (strlen(trim($message)) > 0) ? $this->getMessage($message) : $message,
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
        return view('admin/list_idea', compact('lists', 'idea_files'));
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
        return view('admin/list_idea_done', compact('lists', 'idea_files'));
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
            env('DIR_CHECK'));
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
                        $message .= $this->getErrorMessage('File ' . $filename . ' sai định dạng tên. Mời đổi lại tên.');
                        continue;
                    }
                    if ($file->move(public_path(env('DIR_TMP')), $filename)) {
                        $filter_files[] = $filename;
                    } else {
                        $message .= $this->getErrorMessage('Upload lỗi file :' . $filename . '. Làm ơn thử lại nhé.');
                    }
                } else {
                    $message .= $this->getErrorMessage('File ' . $filename . ' không phải là file ảnh');
                }
            } else {
                $message .= $this->getErrorMessage('File ' . $filename . ' lớn hơn 10MB');
            }
        }
        return array('message' => $message, 'files' => $filter_files);
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
        return view('admin/checking')->with(compact('lists', 'images'));
    }

    private function getWorkingFile($where)
    {
        $return = array();
        $files = \DB::table('working_files')->select('working_id', 'name', 'path')
            ->where($where)
            ->get();
        if (sizeof($files) > 0) {
            $return = array();
            foreach ($files as $file) {
                $return[$file->working_id][] = $file->path . $file->name;
            }
        }
        return $return;
    }

    public function sendCustomer($order_id)
    {
        /*Move file về thư mục done*/
        $where = [
            ['id', '=', $order_id],
        ];
        $working = \DB::table('workings')
            ->select('id', 'number', 'status', 'woo_order_id')
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

    public function redoDesigner($request)
    {
        $rq = $request->all();
        $reason = htmlentities(str_replace("\n", "<br />", trim($rq['reason'])));
        $update = [
            'status' => env('STATUS_WORKING_NEW'),
            'redo' => 1,
            'reason' => $reason,
            'updated_at' => date("Y-m-d H:i:s"),
        ];
        $files = \DB::table('working_files')->select('name', 'path')->where('working_id', $rq['order_id'])->get();
        $deleted = array();
        foreach ($files as $file) {
            $deleted[] = public_path($file->path . $file->name);
        }
        if (\File::delete($deleted)) {
            $status = 'success';
            $save = "Yêu cầu nhân viên làm lại thành công. Tiếp tục kiểm tra những đơn hàng còn lại.";
            \DB::table('workings')->where('id', $rq['order_id'])->update($update);
            \DB::table('working_files')->where('working_id', $rq['order_id'])->delete();
        } else {
            $status = 'error';
            $save = "Yêu cầu nhân viên làm lại thất bại. Mời bạn thử lại";
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
            ['working_files.status', '=', env('STATUS_WORKING_CUSTOMER')]
        ];
        $images = $this->getWorkingFile($where_working_file);
        return view('/admin/review_customer',compact('lists','images'));
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
                    ->select('id','name','path','idea_id')
                    ->where('idea_id',$idea_id)->get();
                $db_google_files = array();
                foreach( $lists as $list)
                {
                    $path = upFile(public_path($list->path),env('GOOGLE_DRIVER_FOLDER_IDEA'));
                    if ($path){
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
    /*End Admin + QC*/
}
