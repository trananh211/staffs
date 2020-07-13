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
use Excel;
use Session;
use Redirect;

class Working extends Model
{
    public $timestamps = true;
    protected $table = 'workings';

    public function log($str)
    {
//        \Log::info($str);
    }

    /*Hàm check Auth ajax*/
    public static function checkAuth()
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
//        echo "<pre>";
        if (\Session::has('date'))
        {
            $date_from = \Session::get('date.date_from');
            $date_to = \Session::get('date.date_to');
        } else {
            $date_from = date("Y-m-d");
            $date_to = date("Y-m-d");
        }
        $date_from = $date_from." 00:00:00";
        $date_to = $date_to." 00:00:00";
        if ($date_from == $date_to)
        {
            $date_to = Carbon::parse($date_to)->addDay(1)->toDateTimeString();
        }
        $order_status = order_status();
        $between = [$date_from, $date_to];
        $lists = \DB::table('woo_orders')
            ->leftjoin('woo_infos','woo_orders.woo_info_id', '=', 'woo_infos.id')
            ->leftjoin('designs', 'woo_orders.design_id', '=', 'designs.id')
            ->leftjoin('tool_categories', 'designs.tool_category_id', '=', 'tool_categories.id')
            ->leftjoin('variations', function ($join){
                $join->on('variations.tool_category_id', '=', 'tool_categories.id')
                    ->on('woo_orders.variation_detail', '=', 'variations.variation_name');
            })
            ->select(
                'woo_orders.id', 'woo_orders.created_at', 'woo_orders.design_id', 'woo_orders.payment_method',
                'woo_orders.price', 'woo_orders.shipping_cost', 'woo_orders.quantity', 'woo_orders.sku',
                'woo_orders.product_name', 'woo_orders.product_id', 'woo_orders.woo_info_id as store_id',
                'woo_orders.variation_detail', 'woo_orders.country', 'woo_orders.state', 'woo_orders.number',
                'designs.tool_category_id', 'tool_categories.name as category_name',
                'variations.price as base_cost',
                'woo_infos.name as store_name'
            )
            ->whereIn('woo_orders.order_status',$order_status)
            ->whereBetween('woo_orders.created_at', $between)->get()->toArray();
//        print_r($lists);
        $stores = array();
        $designs = array();
        $categories = array();
        $products = array();
        $countries = array();
        $states = array();
        $number_order = array();
        $product_by_name = array();
        $tmp_product_code = array();
        foreach ($lists as $item)
        {
            // stores
            $base_cost = ($item->base_cost > 0) ? $item->base_cost : ($item->price/2);
            if(array_key_exists($item->store_id, $stores)){
                $stores[$item->store_id]['item'] += $item->quantity;
                $stores[$item->store_id]['cross'] += ($item->price*$item->quantity) + $item->shipping_cost;
                $stores[$item->store_id]['ship'] += $item->shipping_cost;
                $stores[$item->store_id]['base_cost'] += ($base_cost*$item->quantity);
                $stores[$item->store_id]['net'] += ($item->price*$item->quantity) + $item->shipping_cost - ($base_cost*$item->quantity);
            } else {
                $stores[$item->store_id]['store_name'] = ucwords($item->store_name);
                $stores[$item->store_id]['item'] = $item->quantity;
                $stores[$item->store_id]['cross'] = ($item->price*$item->quantity) + $item->shipping_cost;
                $stores[$item->store_id]['ship'] = $item->shipping_cost;
                $stores[$item->store_id]['base_cost'] = ($base_cost*$item->quantity);
                $stores[$item->store_id]['net'] = ($item->price*$item->quantity) + $item->shipping_cost - ($base_cost*$item->quantity);
            }
            if (!in_array($item->number, $number_order))
            {
                $number_order[] = $item->number;
                $stores[$item->store_id]['order'][] = $item->number;
            }
            // end stores
            // designs
            if (array_key_exists($item->sku, $designs))
            {
                $designs[$item->sku]['item'] += $item->quantity;
            } else {
                $designs[$item->sku]['sku'] = $item->sku;
                $designs[$item->sku]['item'] = $item->quantity;
            }
            // end designs
            // categories
            if (array_key_exists($item->tool_category_id, $categories))
            {
                $categories[$item->tool_category_id]['item'] += $item->quantity;
                $categories[$item->tool_category_id]['net'] += ($item->price*$item->quantity) + $item->shipping_cost - ($base_cost*$item->quantity);
            } else {
                $categories[$item->tool_category_id]['category_name'] = ucwords($item->category_name);
                $categories[$item->tool_category_id]['item'] = $item->quantity;
                $categories[$item->tool_category_id]['net'] = ($item->price*$item->quantity) + $item->shipping_cost - ($base_cost*$item->quantity);
            }
            // end categories
            // products
            if (array_key_exists($item->product_id, $products))
            {
                $products[$item->product_id]['item'] += $item->quantity;
                $products[$item->product_id]['net'] += ($item->price*$item->quantity) + $item->shipping_cost - ($base_cost*$item->quantity);
            } else {
                $products[$item->product_id]['product_name'] = ucwords($item->product_name);
                $products[$item->product_id]['item'] = $item->quantity;
                $products[$item->product_id]['net'] = ($item->price*$item->quantity) + $item->shipping_cost - ($base_cost*$item->quantity);
            }
            // end products

            // countries
            if (array_key_exists($item->country, $countries))
            {
                $countries[$item->country]['item'] += $item->quantity;
            } else {
                $countries[$item->country]['country'] = ucwords($item->country);
                $countries[$item->country]['item'] = $item->quantity;
            }
            // end countries

            // states
            if (array_key_exists($item->state, $states))
            {
                $states[$item->state]['item'] += $item->quantity;
            } else {
                $states[$item->state]['state'] = ucwords($item->state);
                $states[$item->state]['country'] = ucwords($item->country);
                $states[$item->state]['item'] = $item->quantity;
            }
            // end states

            // Product by product code
            $tmp = explode(' ', $item->product_name);
            $product_code = $tmp[sizeof($tmp) - 1];
            if (array_key_exists($product_code, $tmp_product_code))
            {
                $product_by_name[$product_code]['item'] += 1;
                $product_by_name[$product_code]['net'] += ($item->price*$item->quantity) + $item->shipping_cost - ($base_cost*$item->quantity);
            } else {
                $tmp_product_code[$product_code] = $product_code;
                $product_by_name[$product_code]['item'] = 1;
                $product_by_name[$product_code]['net'] = ($item->price*$item->quantity) + $item->shipping_cost - ($base_cost*$item->quantity);
            }
            // End product by product code
        }
        $stores = collect($stores);
        $stores = $stores->sortByDesc('item');

        $countries = collect($countries);
        $countries = $countries->sortByDesc('item');
        $countries = $countries->take(10);

        $designs = collect($designs);
        $designs = $designs->sortByDesc('item');
        $designs = $designs->take(10);

        $states = collect($states);
        $states = $states->sortByDesc('item');
        $states = $states->take(10);

        $categories = collect($categories);
        $categories = $categories->sortByDesc('item');
        $categories = $categories->take(10);

        $products = collect($products);
        $products = $products->sortByDesc('item');
        $products = $products->take(10);

        $product_by_name = collect($product_by_name);
        $product_by_name = $product_by_name->sortByDesc('item');

        return view('/admin/dashboard')
            ->with(compact( 'data','stores', 'designs', 'countries', 'states', 'categories', 'products','product_by_name'));
    }

    public function staffDashboard($data)
    {
        $uid = Auth::id();
        // số file đang làm việc
        $where_working = [
            ['workings.worker_id', '=', $uid],
            ['workings.status', '=', env('STATUS_WORKING_NEW')]
        ];
        $week_day = getTimeAgo(7);
        $month_day = getTimeAgo(30);
        // số file đã làm việc trong tuần
        $where_working_in_week = [
            ['workings.worker_id', '=', $uid],
            ['workings.status', '>', env('STATUS_WORKING_NEW')],
            ['workings.created_at', '>=', "'" . $week_day . "'"],
        ];

        // số file đã làm việc trong 30 ngày vừa qua
        $where_working_in_month = [
            ['workings.worker_id', '=', $uid],
            ['workings.status', '=', env('STATUS_WORKING_DONE')],
            ['workings.created_at', '>=', "'" . $month_day . "'"],
        ];
        $working = $this->countStaffWorking($where_working);
        $file_work_inweek = $this->countStaffWorking($where_working_in_week);
        $file_work_inmonth = $this->countStaffWorking($where_working_in_month);

        $reports = [
            'working' => $working,
            'work_in_week' => $file_work_inweek,
            'work_in_month' => $file_work_inmonth
        ];
        // Hiển thị tất cả các job đã làm và trạng thái hoàn thành
        $where = [
            ['workings.worker_id', '=', $uid],
        ];
//        $lst_jobs = $this->orderStaff($where);
        return view('/staff/dashboard')
            ->with(compact('data','reports'));
    }

    private function countStaffWorking($where)
    {
        $working = \DB::table('workings')->where($where)->count();
        return $working;
    }

    public function qcDashboard($data)
    {
        $uid = Auth::id();
        return view('/staff/qc_dashboard')
            ->with(compact('data'));
    }

    public function dashboardDate($request)
    {
        $rq = $request->all();
//        $date_from = Carbon::parse($rq['date_from']);
//        $date_to = Carbon::parse($rq['date_to']);
        $date_from = $rq['date_from'];
        $date_to = $rq['date_to'];
        \Session::put('date.date_from', $date_from);
        \Session::put('date.date_to', $date_to);
        return \Redirect::back();
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
            ->leftJoin('trackings as t', 'woo_orders.number', '=', 't.order_id')
            ->leftjoin('workings', function ($join) {
                $join->on('workings.design_id', '=', 'woo_orders.design_id')
                    ->on('workings.product_id', '=', 'woo_orders.product_id')
                    ->on('workings.store_id', '=', 'woo_orders.woo_info_id');
            })
            ->select(
                'woo_orders.id', 'woo_orders.number', 'woo_orders.product_name',
                'woo_orders.quantity', 'woo_orders.price', 'woo_orders.created_at', 'woo_orders.payment_method',
                'woo_infos.name', 'woo_orders.order_status', 'woo_orders.email',
                'woo_orders.sku', 'woo_orders.variation_full_detail', 'woo_orders.variation_detail',
                't.tracking_number', 't.status as tracking_status',
                'workings.id as working_id', 'workings.status'
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

    private static function orderStaff($where)
    {
        $lists = \DB::table('workings')
            ->leftjoin('woo_products', function ($join) {
                $join->on('workings.product_id', '=', 'woo_products.product_id')
                    ->on('workings.store_id', '=', 'woo_products.woo_info_id');
            })
            ->leftjoin('designs', 'workings.design_id', '=', 'designs.id')
            ->leftjoin('woo_orders', function ($join) {
                $join->on('workings.design_id', '=', 'woo_orders.design_id')
                    ->on('workings.product_id', '=', 'woo_orders.product_id')
                    ->on('workings.store_id', '=', 'woo_orders.woo_info_id');
            })
            ->leftjoin('tool_categories', function ($join) {
                $join->on('workings.design_id', '=', 'designs.id')
                    ->on('designs.tool_category_id', '=', 'tool_categories.id');
            })
            ->leftjoin('users as worker', 'workings.worker_id', '=', 'worker.id')
            ->leftjoin('users as qc', 'workings.qc_id', '=', 'qc.id')
            ->select(
                'workings.id', 'workings.status', 'workings.updated_at',
                'workings.qc_id', 'workings.worker_id', 'workings.reason', 'workings.redo',
                'designs.id as design_id', 'designs.sku', 'designs.variation', 'designs.tool_category_id',
                'tool_categories.name as tool_category_name',
                'woo_orders.detail', 'woo_orders.customer_note',
                'worker.id as worker_id', 'worker.name as worker_name',
                'qc.name as qc_name',
                'woo_products.name', 'woo_products.permalink', 'woo_products.image'
            )
            ->where($where)
            ->orderBy('workings.id', 'ASC')
            ->limit(env('CHECKING_JOB_LIMIT'))
            ->get()
            ->toArray();

        $array_used = array();
        foreach ($lists as $key => $list)
        {
            if (!in_array($list->design_id, $array_used))
            {
                $array_used[] = $list->design_id;
            } else {
                unset($lists[$key]);
            }
        }
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
                $this->log('-- Nhân viên ' . $username . ' xin job mới.');
                $jobs = DB::table('designs')
                    ->select('id', 'product_id', 'store_id', 'sku', 'variation')
                    ->where('status', env('STATUS_WORKING_NEW'))
                    ->orderBy('sku', 'ASC')
                    ->limit(env("STAFF_GET_JOB_LIMIT"))
                    ->get()->toArray();
                if (sizeof($jobs) > 0) {
                    $data_workings = array();
                    $data_id_driver = array();
                    //gộp toàn bộ order vào cùng 1 job
                    foreach ($jobs as $design) {
                        $data_workings[] = [
                            'design_id' => $design->id,
                            'store_id' => $design->store_id,
                            'product_id' => $design->product_id,
                            'worker_id' => $uid,
                            'status' => env('STATUS_WORKING_NEW'),
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                        $data_id_driver[] = $design->id;
                    }

                    if (sizeof($data_workings) > 0) {
                        \DB::beginTransaction();
                        try {
                            \DB::table('workings')->insert($data_workings);
                            \DB::table('designs')
                                ->whereIn('id', $data_id_driver)
                                ->update(['status' => env('STATUS_WORKING_CHECK')]);
                            $return = true;
                            $save = "Chia " . sizeof($data_workings) . " order cho '" . $username . "' thanh cong.";
                            \Session::flash('success', 'Nhận việc thành công. Vui lòng hoành thành sớm.');
                            \DB::commit(); // if there was no errors, your query will be executed
                        } catch (\Exception $e) {
                            $return = false;
                            $save = "Chia " . sizeof($data_workings) . " order cho '" . $username . "' thất bại.";
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
                $lsts = \DB::table('workings')
                    ->leftjoin('designs', 'designs.id', '=', 'workings.design_id')
                    ->select('workings.id', 'designs.sku')
                    ->where([
                        'workings.status' => env('STATUS_WORKING_NEW'),
                        'workings.worker_id' => $uid
                    ])->get()->toArray();
                if (sizeof($lsts) > 0) {
                    $ar_filecheck = array();
                    foreach ($lsts as $lst) {
                        $ar_filecheck[$lst->sku][] = $lst->id;
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
                            $message .= getErrorMessage('File ' . $file . ' sai tên hoặc Bạn không làm job này.');
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
                                    $img .= thumb_c('/' . $thumb, 50, $f);
                                } else {
                                    $message .= getErrorMessage('File ' . $f . ' không thể tải lên lúc này. Mời thử lại');
                                }
                            }
                        } else {
                            $message .= getErrorMessage('Bạn tải lên thiếu file mockup. Bạn cần tải thêm để hoàn thành job.');
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
            } else {
                $message .= getErrorMessage('File ảnh bạn tải lên không đúng định dạng yêu cầu. Thiếu -PID- hoặc sai định dạng ảnh.');
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

    public function staffSkipJob($working_id)
    {
        \DB::beginTransaction();
        try {
            $status = 'success';
            $update = [
                'status' => env('STATUS_SKIP'),
                'updated_at' => date("Y-m-d H:i:s")
            ];
            $workings = \DB::table('workings')->select('design_id')->where('id',$working_id)->first();
            \DB::table('workings')->where('id',$working_id)->update($update);
            \DB::table('designs')->where('id',$workings->design_id)->update($update);
            $message = 'Bỏ qua job này thành công. Mời bạn làm job tiếp theo.';
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $status = 'error';
            $message = 'Bỏ qua job này thất bại. Yêu cầu gửi số Id của Job cho quản lý của bạn ngay.';
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($status, $message);
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
            if ($file->getSize() <= env('UPLOAD_SIZE_MAX')) {
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
                $message .= getErrorMessage('File ' . $filename . ' lớn hơn '.(int)(env('UPLOAD_SIZE_MAX')/1000000).' MB');
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

    public function getChecking()
    {
        $where = [
            ['workings.status', '=', env('STATUS_WORKING_CHECK')]
        ];
        $lists = $this->orderStaff($where);
        $where_working_file = [
            ['working_files.status', '=', env('STATUS_WORKING_CHECK')]
        ];
        $images = $this->getWorkingFile($where_working_file);
        $tool_categories = \DB::table('tool_categories')->select('id','name')->get()->toArray();
        $variations = \DB::table('variations')
            ->select('id','variation_name', 'variation_real_name','tool_category_id')
            ->get()->toArray();
        $data = infoShop();
        return view('admin/checking')
            ->with(compact('lists', 'images', 'data', 'tool_categories','variations'));
    }

    public function checkWorking()
    {
        $where = [
            ['workings.status', '=', env('STATUS_WORKING_NEW')]
        ];
        $lists = $this->orderStaff($where);
        $data = infoShop();
        return view('admin/working')->with(compact('data', 'lists'));
    }

    public function getWorkingFile($where)
    {
        $return = array();
        $files = \DB::table('working_files')
            ->select('working_id', 'name', 'thumb','base_name')
            ->where($where)
            ->get();
        if (sizeof($files) > 0) {
            $return = array();
            foreach ($files as $key => $file) {
                $return[$file->working_id][$key]['name'] = $file->name;
                $return[$file->working_id][$key]['thumb'] = $file->thumb;
                $return[$file->working_id][$key]['base_name'] = $file->base_name;
            }
        }
        return $return;
    }

    public function sendCustomer($working_id)
    {
        $where = [
            ['workings.id', '=', $working_id],
            ['wfl.is_mockup', '=', 1],
        ];
        $working = \DB::table('workings')
            ->join('working_files as wfl', 'workings.id', '=', 'wfl.working_id')
            ->select(
                'workings.design_id',
                'wfl.path', 'wfl.name as file_name'
            )
            ->where($where)
            ->first();
        if ($working !== NULL) {
            \DB::table('workings')->where('id', $working_id)
                ->update([
                    'status' => env('STATUS_WORKING_CUSTOMER'),
                    'qc_id' => Auth::id(),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            \DB::table('designs')->where('id', $working->design_id)
                ->update([
                    'status' => env('STATUS_WORKING_CUSTOMER'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            \DB::table('working_files')->where('working_id', $working_id)
                ->update([
                    'status' => env('STATUS_WORKING_CUSTOMER'),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            $list_customer = \DB::table('woo_orders as wod')
                ->leftjoin('woo_infos as wif','wod.woo_info_id', '=', 'wif.id')
                ->select(
                    'wod.email as customer_email', 'wod.fullname as customer_name', 'wod.number',
                    'wif.name', 'wif.email', 'wif.password', 'wif.host', 'wif.port', 'wif.security'
                )
                ->where('design_id',$working->design_id)
                ->get()->toArray();
            if( sizeof($list_customer) > 0)
            {
                foreach($list_customer as $info)
                {
                    $data = array();
                    $data = json_decode(json_encode($info), true);;
                    $data['file'] = public_path($working->path . $working->file_name);
                    $this->sendEmailToCustomer($data);
                }
            }
            $status = 'success';
            $message = "Thành công. Tiếp tục kiểm tra các đơn hàng còn lại.";
        } else {
            $status = 'error';
            $message = "Xảy ra lỗi. Mời bạn thử lại. Nếu vẫn không được hãy báo với quản lý của bạn và kiểm tra đơn kế tiếp";
        }
        \Session::flash($status, $message);
        return back();
    }

    /*Todo: Xây dựng hàm gửi email tới khách hàng ở đây */
    private function sendEmailToCustomer($data)
    {
        $info = (object) $data;
        $info->email_to = $data['customer_email'];
        $info->title = '[ ' . $data['name'] . ' ] Update information about order ' . $data['number'];
        $info->file = $data['file'];
        $info->body = "Dear " . $data['customer_name'] . ",
We send you this email with information about " . $data['number'] . " order. 
We send detailed information about the design in the attached file below. 
If you want to resubmit your order redesign request, please reply to the message within 24 hours from the time you receive this email, after 24 hours we will move on to the next stage. 
If you are satisfied with the product, please do not reply to this email.
Thank you for your purchase at our store. Wish you a good day and lots of luck.
            ";
        dispatch(new SendPostEmail($info));

    }

    public function axReSendEmail($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            $working_id = $rq['working_id'];
            $design_id = $rq['design_id'];

            $where = [
                ['workings.id', '=', $working_id],
                ['wfl.is_mockup', '=', 1],
            ];
            $working = \DB::table('workings')
                ->join('working_files as wfl', 'workings.id', '=', 'wfl.working_id')
                ->select(
                    'workings.design_id',
                    'wfl.path', 'wfl.name as file_name'
                )
                ->where($where)
                ->first();
            if ($working !== NULL) {
                \DB::table('workings')->where('id', $working_id)
                    ->update([
                        'status' => env('STATUS_WORKING_CUSTOMER'),
                        'qc_id' => Auth::id(),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                \DB::table('designs')->where('id', $working->design_id)
                    ->update([
                        'status' => env('STATUS_WORKING_CUSTOMER'),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                \DB::table('working_files')->where('working_id', $working_id)
                    ->update([
                        'status' => env('STATUS_WORKING_CUSTOMER'),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                $list_customer = \DB::table('woo_orders as wod')
                    ->leftjoin('woo_infos as wif','wod.woo_info_id', '=', 'wif.id')
                    ->select(
                        'wod.email as customer_email', 'wod.fullname as customer_name', 'wod.number',
                        'wif.name', 'wif.email', 'wif.password', 'wif.host', 'wif.port', 'wif.security'
                    )
                    ->where('design_id',$working->design_id)
                    ->get()->toArray();
                if( sizeof($list_customer) > 0)
                {
                    foreach($list_customer as $info)
                    {
                        $data = array();
                        $data = json_decode(json_encode($info), true);;
                        $data['file'] = public_path($working->path . $working->file_name);
                        $this->sendEmailToCustomer($data);
                    }
                }
                $status = 'success';
                $message = "Gửi lại email cho khách thành công.";
            } else {
                $status = 'error';
                $message = "Xảy ra lỗi. Mời bạn thử lại. Nếu vẫn không được hãy báo với quản lý của bạn.";
            }

            return response()->json([
                'status' => $status,
                'message' => $message
            ]);
        }
    }

    public function redoingJobStaff($working_id)
    {
        \DB::beginTransaction();
        try {
            $update = [
                'status' => env('STATUS_WORKING_NEW'),
                'updated_at' => date("Y-m-d H:i:s"),
            ];
            $files = \DB::table('working_files')
                ->select('name', 'path', 'thumb')
                ->where('working_id', $working_id)
                ->get();
            $deleted = array();
            foreach ($files as $file) {
                $deleted[] = public_path($file->path . $file->name);
                $deleted[] = public_path($file->thumb);
            }
            if (\File::delete($deleted)) {
                $status = 'success';
                $save = "Yêu cầu làm lại thành công.";
                \DB::table('workings')->where('id', $working_id)->update($update);
                \DB::table('working_files')->where('working_id', $working_id)->delete();
            } else {
                $status = 'error';
                $save = "[Redo] Yêu cầu làm lại thất bại. Mời bạn thử lại";
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

    public function redoDesigner($request)
    {
        \DB::beginTransaction();
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
        $workers = \DB::table('users')->select('id','name')->where('level',3)->get()->toArray();
        $where = [
            ['workings.status', '=', env('STATUS_WORKING_CUSTOMER')],
        ];
        $lists = $this->reviewWork($where, 0);
        $where_working_file = [
            ['working_files.status', '=', env('STATUS_WORKING_CUSTOMER')],
            ['working_files.thumb', '!=', 'NULL']
        ];
        $images = $this->getWorkingFile($where_working_file);
        $tmp_variations = $this->getVariation();
        $variations = $tmp_variations['variations'];
        $all_variations = $tmp_variations['all_variations'];
        $data = infoShop();
        return view('/admin/review_customer',
            compact('lists', 'images', 'data', 'workers', 'variations', 'all_variations'));
    }

    public function listJobDone()
    {
        $workers = \DB::table('users')->select('id','name')->where('level',3)->get()->toArray();
        $where = [
            ['workings.status', '>=', env('STATUS_WORKING_DONE')],
            ['workings.status', '<=', env('STATUS_WORKING_MOVE')],
        ];
        $lists = $this->reviewWork($where, 1);
        $where_working_file = [
            ['working_files.status', '>=', env('STATUS_WORKING_DONE')],
            ['working_files.status', '<=', env('STATUS_WORKING_MOVE')],
            ['working_files.thumb', '!=', 'NULL']
        ];
        $images = $this->getWorkingFile($where_working_file);
        $tmp_variations = $this->getVariation();
        $variations = $tmp_variations['variations'];
        $all_variations = $tmp_variations['all_variations'];
        $data = infoShop();
        return view('/admin/review_customer',
            compact('lists', 'images', 'data', 'workers', 'variations', 'all_variations'));
    }

    public function searchWorkJob($request)
    {
        $rq = $request->all();
        \Session::put('search.keyword', trim($rq['search_job']));
        $search = htmlentities(str_replace(" ", "", strtolower(trim($rq['search_job']))));
        $lists = array();
        $lsts = \DB::table('designs')
            ->leftjoin('workings', 'workings.design_id', '=', 'designs.id')
            ->leftjoin('users as worker', 'workings.worker_id', '=', 'worker.id')
            ->leftjoin('users as qc', 'workings.qc_id', '=', 'qc.id')
            ->leftjoin('woo_products', 'workings.product_id', '=', 'woo_products.product_id')
            ->select(
                'workings.id as working_id', 'workings.status', 'workings.updated_at',
                'workings.qc_id', 'workings.worker_id', 'workings.reason', 'workings.redo','workings.updated_at',
                'designs.sku','designs.variation', 'designs.status', 'designs.tool_category_id', 'designs.id as design_id',
                'worker.id as worker_id', 'worker.name as worker_name', 'qc.id as qc_id', 'qc.name as qc_name',
                'woo_products.name', 'woo_products.permalink', 'woo_products.image'
            )
            ->where('designs.sku', 'LIKE', '%'.$search.'%')
            ->orderBy('designs.sku','ASC')
            ->get()->toArray();
        if (sizeof($lsts) > 0) {
            $workers = \DB::table('users')->select('id','name')->where('level',3)->get()->toArray();
            $lst_design_id = array();
            $list_working_id = array();
            foreach ($lsts as $lst) {
                $lst_design_id[$lst->design_id] = $lst->design_id;
                $list_working_id[$lst->working_id] = $lst->working_id;
            }
            $lst_orders = \DB::table('woo_orders')
                ->select(
                    'woo_orders.id', 'woo_orders.number', 'woo_orders.email', 'woo_orders.fullname',
                    'woo_orders.payment_method', 'woo_orders.sku', 'woo_orders.design_id', 'woo_orders.variation_full_detail'
                )
                ->whereIn('design_id', $lst_design_id)
                ->get()->toArray();
            if (sizeof($lst_orders) > 0) {
                $lst_designs = array();
                foreach ($lst_orders as $lst_order) {
                    $lst_designs[$lst_order->design_id][] = json_decode(json_encode($lst_order, true), true);
                }
            }
            // dồn toàn bộ woo_orders vào trong 1 array với design
            $lsts = json_decode(json_encode($lsts, true), true);
            foreach ($lsts as $key => $lst) {
                if (array_key_exists($lst['design_id'], $lst_designs)) {
                    $lists[$key]['info'] = $lst;
                    $lists[$key]['orders'] = $lst_designs[$lst['design_id']];
                }
            }
            // lấy danh sách image
            $images = array();
            $files = \DB::table('working_files')
                ->select('working_id', 'name', 'thumb','base_name')
                ->whereIn('working_id', $list_working_id)
                ->get();
            if (sizeof($files) > 0) {
                foreach ($files as $key => $file) {
                    $images[$file->working_id][$key]['name'] = $file->name;
                    $images[$file->working_id][$key]['thumb'] = $file->thumb;
                    $images[$file->working_id][$key]['base_name'] = $file->base_name;
                }
            }
            $tmp_variations = $this->getVariation();
            $variations = $tmp_variations['variations'];
            $all_variations = $tmp_variations['all_variations'];
            $data = infoShop();
            return view('/admin/search_working_job',
                compact('lists', 'images', 'data', 'workers', 'variations', 'all_variations'));
        } else {
            $data = infoShop();
            $status = 'error';
            $message = 'Không tìm thấy SKU bạn đang tìm kiếm';
            return view('/admin/search_working_job',compact('lists','data'))->with($status, $message);
        }
    }

    public function editCategoryFulfill($request)
    {
        $rq = $request->all();
        \DB::beginTransaction();
        try {
            $status = 'success';
            $update = [
                'type_fulfill_id' => $rq['type_fulfill_id'],
                'exclude_text' => (trim($rq['exclude_text']) !== '')? trim($rq['exclude_text']): NULL,
                'updated_at' => date("Y-m-d H:i:s")
            ];
            \DB::table('tool_categories')->where('id',$rq['tool_category_id'])->update($update);
            $message = 'Cập nhật thông tin cho category này thành công. Mời bạn làm job tiếp theo.';
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $status = 'error';
            $message = 'Cập nhật thông tin cho category này thất bại.';
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($status, $message);
    }

    public function keepWorkingJob($working_id)
    {
        \DB::beginTransaction();
        try {
            $status = 'success';
            $update = [
                'status' => env('STATUS_WORKING_NEW'),
                'updated_at' => date("Y-m-d H:i:s")
            ];
            $workings = \DB::table('workings')->select('design_id')->where('id',$working_id)->first();
            \DB::table('workings')->where('id',$working_id)->update($update);
            \DB::table('designs')->where('id',$workings->design_id)->update($update);
            $message = 'Yêu cầu giữ lại Job này thành công. Mời bạn làm job tiếp theo.';
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $status = 'error';
            $message = 'Yêu cầu giữ lại Job này thất bại. Gửi số Id của Job cho quản lý của bạn ngay.';
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($status, $message);
    }

    public function jobCancel()
    {
        $where = [
            ['workings.status', '=', env('STATUS_SKIP')]
        ];
        $lists = $this->orderStaff($where);
        $where_working_file = [
            ['working_files.status', '=', env('STATUS_SKIP')]
        ];
        $images = $this->getWorkingFile($where_working_file);
        $tool_categories = \DB::table('tool_categories')->select('id','name')->get()->toArray();
        $variations = \DB::table('variations')
            ->select('id','variation_name', 'variation_real_name','tool_category_id')
            ->get()->toArray();
        $data = infoShop();
        return view('admin/job_cancel')
            ->with(compact('lists', 'images', 'data', 'tool_categories','variations'));
    }

    private function getVariation()
    {
        $lst = \DB::table('variations')->select('variation_name','variation_real_name','tool_category_id')->get()->toArray();
        $variations = array();
        $all_variations = array();
        foreach ($lst as $item)
        {
            $dt = ($item->variation_real_name != '')? $item->variation_real_name : $item->variation_name;
            $all_variations[$item->variation_name] = $dt;
            if ($item->tool_category_id != '')
            {
                $variations[$item->tool_category_id][$item->variation_name] = $dt;
            }
        }
        return array(
            'variations' => $variations,
            'all_variations' => $all_variations
        );
    }

    public function supplier()
    {
        $where = [
            ['workings.status', '=', env('STATUS_WORKING_DONE')],
        ];
        return $this->reviewWork($where);
    }

    public function axRedoNewSKU($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            \DB::beginTransaction();
            try {
                $rq = $request->all();
                $layout = '';
                $order_id = $rq['order_id'];
                $working_id = $rq['working_id'];
                $design_id = $rq['design_id'];
                $new_variation = trim($rq['new_variation']);
                $sku = $rq['sku'];
                $worker_id = $rq['worker_id'];
                $reason = '1. Khách đổi sku thành : '.$sku."<br />";
                $reason .= '2. Khách đổi size thành : '.$new_variation."<br />";
                $reason .= '3.'.htmlentities(str_replace("\n", "<br />", trim($rq['reason'])));

                //kiem tra xem day la 1 hay nhieu design
                $checks = \DB::table('woo_orders')->select('id')->where('design_id', $design_id)->count();
                /** Kiểm tra xem đã tồn tại SKU và variation đấy hay chưa**/
                $check_exist_design = \DB::table('designs')->select('id')
                    ->where('sku', $sku)
                    ->where('variation', $new_variation)
                    ->first();
                // nếu tồn tại rồi. Đổi sang design tồn tại
                if ($check_exist_design != NULL) {
                    // nếu chỉ có 1 designs. Xóa design hiện tai. working hiện tại. working file hiện tại
                    if ($checks == 1) {
                        \DB::table('designs')->where('id', $design_id)->delete();
                        \DB::table('workings')->where('id', $working_id)->delete();
                        // xoa working_files
                        $files = \DB::table('working_files')
                            ->select('name', 'path', 'thumb')
                            ->where('working_id', $working_id)
                            ->get();
                        $deleted = array();
                        foreach ($files as $file) {
                            $deleted[] = public_path($file->path . $file->name);
                            $deleted[] = public_path($file->thumb);
                        }
                        \File::delete($deleted);
                        \DB::table('working_files')->where('working_id', $working_id)->delete();
                        $layout = 'refresh';
                    } else {
                        $layout = 'keep';
                    }
                    // Update thong tin new design_id vao woo_orders
                    \DB::table('woo_orders')->where('id', $order_id)->update([
                        'sku' => $sku,
                        'variation_detail' => $new_variation,
                        'design_id' => $check_exist_design->id,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    $alert = 'success';
                    $message = '<small class="green-text"> Đã redo Job thành công. Mời bạn kiểm tra các Job tiếp theo.</small>';
                } else {
                    /** Tao design id moi **/
                    // lay thong tin design cu
                    $design_old = \DB::table('designs')->select('*')->where('id', $design_id)->first();
                    $new_data_design = [
                        'product_name' => $design_old->product_name,
                        'product_id' => $design_old->product_id,
                        'store_id' => $design_old->store_id,
                        'sku' => $sku,
                        'variation' => $new_variation,
                        'status' => env('STATUS_WORKING_NEW'),
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];

                    $new_design_id = 1;
                    $new_design_id = \DB::table('designs')->insertGetId($new_data_design);
                    //update vao woo_orders va workings
                    if ($new_design_id) {
                        // lay du lieu cua working_old
                        $workings_old = \DB::table('workings')->select('store_id', 'product_id', 'reason')->where('id', $working_id)->first();

                        // neu con nhieu design
                        if ($checks > 1) {
                            //tao moi working
                            $data_workings = [
                                'design_id' => $new_design_id,
                                'store_id' => $workings_old->store_id,
                                'product_id' => $workings_old->product_id,
                                'worker_id' => $worker_id,
                                'qc_id' => $uid,
                                'status' => env('STATUS_WORKING_NEW'),
                                'redo' => 1,
                                'reason' => $workings_old->reason . '<br>' . $reason,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            \DB::table('workings')->insert($data_workings);
                            $layout = 'keep';
                        } else {
                            // cap nhat working id cu
                            \DB::table('workings')->where('id', $working_id)->update([
                                'design_id' => $new_design_id,
                                'worker_id' => $worker_id,
                                'qc_id' => $uid,
                                'redo' => 1,
                                'status' => env('STATUS_WORKING_NEW'),
                                'reason' => $workings_old->reason . '<br>' . $reason,
                                'updated_at' => date("Y-m-d H:i:s")
                            ]);
                            // xoa working_files
                            $files = \DB::table('working_files')
                                ->select('name', 'path', 'thumb')
                                ->where('working_id', $order_id)
                                ->get();
                            $deleted = array();
                            foreach ($files as $file) {
                                $deleted[] = public_path($file->path . $file->name);
                                $deleted[] = public_path($file->thumb);
                            }
                            \File::delete($deleted);
                            \DB::table('working_files')->where('working_id', $working_id)->delete();
                            // xoa designs
                            \DB::table('designs')->where('id', $design_id)->delete();
                            $layout = 'refresh';
                        }
                        // Update thong tin new design_id vao woo_orders
                        \DB::table('woo_orders')->where('id', $order_id)->update([
                            'sku' => $sku,
                            'variation_detail' => $new_variation,
                            'design_id' => $new_design_id,
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                        $alert = 'success';
                        $message = '<small class="green-text"> Đã redo Job thành công. Mời bạn kiểm tra các Job tiếp theo.</small>';
                    } else {
                        $alert = 'error';
                        $message = '<small class="red-text"> Xảy ra lỗi nội bộ. Mời bạn tải lại trang và thử lại.</span>';
                    }
                }
                \DB::commit(); // if there was no errors, your query will be executed
            } catch (\Exception $e) {
                $status = 'error';
                $message = "Yêu cầu chưa được thực hiện. Vui lòng tải lại trang và tiếp tục với đơn khác nếu vẫn lỗi.";
                \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            }
        } else {
            $alert = 'error';
            $message = '<small class="red-text"> Đã hết phiên. Mời bạn đăng nhập và thử lại.</span>';
        }
        return response()->json([
            'message' => $message,
            'status' => $alert,
            'layout' => $layout
        ]);
    }

    /*Hàm hiển thị danh sách review của customer*/
    private static function reviewWork($where, $done = NULL)
    {
        $lists = array();
        $lsts = \DB::table('workings')
            ->leftjoin('designs', 'workings.design_id', '=', 'designs.id')
            ->leftjoin('users as worker', 'workings.worker_id', '=', 'worker.id')
            ->leftjoin('users as qc', 'workings.qc_id', '=', 'qc.id')
            ->leftjoin('woo_products', 'workings.product_id', '=', 'woo_products.product_id')
            ->select(
                'workings.id', 'workings.status', 'workings.updated_at', 'workings.design_id',
                'workings.qc_id', 'workings.worker_id', 'workings.reason', 'workings.redo','workings.updated_at',
                'designs.sku','designs.variation', 'designs.status', 'designs.tool_category_id',
                'worker.id as worker_id', 'worker.name as worker_name', 'qc.id as qc_id', 'qc.name as qc_name',
                'woo_products.name', 'woo_products.permalink', 'woo_products.image'
            )
            ->where($where)
            ->orderBy('designs.sku','ASC')
            ->limit(env('CHECKING_JOB_LIMIT'))
            ->get()->toArray();
        if (sizeof($lsts) > 0)
        {
            $lst_design_id = array();
            foreach( $lsts as $lst)
            {
                $lst_design_id[$lst->design_id] = $lst->design_id;
            }
            // lấy toàn bộ danh sách woo_orders ra để hiển thị ra ngoài
            if ($done == 0)
            {
                $where_order = [
                    ['status', '<', env('STATUS_WORKING_DONE')]
                ];
            } else {
                $where_order = [
                    ['status', '>=', env('STATUS_WORKING_NEW')]
                ];
            }

            $lst_orders = \DB::table('woo_orders')
                ->select(
                    'woo_orders.id','woo_orders.number', 'woo_orders.email', 'woo_orders.fullname',
                    'woo_orders.payment_method','woo_orders.sku','woo_orders.design_id', 'woo_orders.variation_full_detail'
                )
                ->whereIn('design_id',$lst_design_id)
                ->where($where_order)
                ->get()->toArray();
            $lst_designs = array();
            if (sizeof($lst_orders) > 0)
            {
                $lst_designs = array();
                foreach ($lst_orders as $lst_order)
                {
                    $lst_designs[$lst_order->design_id][] = json_decode(json_encode($lst_order,true),true);
                }
            }
            // dồn toàn bộ woo_orders vào trong 1 array với design
            $lsts = json_decode(json_encode($lsts,true),true);
            foreach ($lsts as $key => $lst)
            {
                if (array_key_exists($lst['design_id'], $lst_designs))
                {
                    $lists[$key]['info'] = $lst;
                    $lists[$key]['orders'] = $lst_designs[$lst['design_id']];
                }
            }
        }
        return $lists;
    }

    public function eventQcDone($request)
    {
        if (Auth::check()) {
            $working_id = $request->all()['working_id'];
            $design_id = $request->all()['design_id'];
            \DB::beginTransaction();
            try {
                $update = [
                    'status' => env('STATUS_WORKING_DONE'),
                    'updated_at' => date("Y-m-d H:i:s"),
                ];
                \DB::table('workings')->where('id', $working_id)->update($update);
                \DB::table('designs')->where('id', $design_id)->update($update);
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
            $design_id = $rq['design_id'];
            \DB::beginTransaction();
            try {
                \DB::table('workings')->where('id', $working_id)->delete();
                \DB::table('designs')->where('id', $design_id)
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

    public function axGiveJobStaff($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            $working_id = $rq['working_id'];
            $staff_id = $rq['staff_id'];
            \DB::beginTransaction();
            try {
                \DB::table('workings')->where('id', $working_id)->update(['worker_id' => $staff_id]);
                \DB::table('working_files')->where('working_id', $working_id)->update(['worker_id' => $staff_id]);

                $status = 'success';
                $message = 'Chuyển Job thành công. Tiếp tục công việc của bạn.';
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

    public function axChooseVariations($request)
    {
        $uid = $this->checkAuth();
        $status = 'error';
        $message = '';
        if ($uid) {
            $rq = $request->all();
            $tool_category_id = $rq['cat_id'];
            $category_name = $rq['cat_name'];

            //lấy toàn bộ danh sách category ra ngoài
            $categories = \DB::table('tool_categories')->pluck('name','id')->toArray();
            //lấy toàn bộ danh sách variation chưa được chọn trong 1 store id
            $variations = \DB::table('variations')
                ->select('id','variation_name', 'tool_category_id')
                ->orderBy('tool_category_id','ASC')
                ->orderBy('variation_name')
                ->get()->toArray();
            // lấy danh sách variations được chọn trong category này
            $variations_choosed = \DB::table('variations')
                ->where('tool_category_id', $tool_category_id)
                ->pluck('id','variation_name')
                ->toArray();
            $list_variations = array();
            foreach ($variations as $item)
            {
                $selected = 0;
                if (in_array($item->id, $variations_choosed))
                {
                    $selected = 1;
                }
                $tool_category_name = '';
                if (array_key_exists($item->tool_category_id, $categories))
                {
                    $tool_category_name = $categories[$item->tool_category_id];
                }
                $list_variations[] = [
                    'id' => $item->id,
                    'variation_name' => $item->variation_name,
                    'selected' => $selected,
                    'tool_category_name' => $tool_category_name
                ];
            }

            if (sizeof($variations_choosed) > 0)
            {
                $status = 'success';
                $message .= 'Category : '.$category_name.' đã từng được chọn variations trước đó rồi. Mời bạn kiểm tra lại';
            } else {
                $status = 'success';
                $message .= 'Category : '.$category_name.' chưa được chọn variations lần nào. Mời bạn chọn';
            }
            if (sizeof($variations) == 0)
            {
                $status = 'error';
                $message = 'Chưa có variations nào. Mời bạn tạo variation ở dưới trước nhé.';
            }
            $response = [
                'result' => $status,
                'message' => $message,
                'variations' => $list_variations,
                'cat_id' => $tool_category_id,
                'cat_name' => $category_name
            ];
        } else {
            $response = [
                'result' => 'error',
                'message' => 'Đã hết phiên làm việc. Mời bạn đăng nhập lại.',
                'variations' => []
            ];
        }
        return response()->json($response);
    }

    public function addListVariation($request)
    {
        $rq = $request->all();
        \DB::beginTransaction();
        try {
            $tool_category_id = $rq['tool_category_id'];
            \DB::table('designs')->where('tool_category_id', $tool_category_id)->update(['tool_category_id' => null]);
            if (array_key_exists('variations', $rq))
            {
                $variations = $rq['variations'];
                \DB::table('variations')->where('tool_category_id', $tool_category_id)
                    ->update([
                        'tool_category_id' => null
                    ]);
                \DB::table('variations')->whereIn('id', $variations)
                    ->update([
                        'tool_category_id' => $tool_category_id,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                //update tool_variation_id to design
                $lst_variations = \DB::table('variations')->whereIn('id', $variations)->pluck('variation_name')->toArray();
                \DB::table('designs')->whereIn('variation', $lst_variations)->update(['tool_category_id' => $tool_category_id]);
                $message = 'Thêm Variation vào Category thành công.';
            } else {
                \DB::table('variations')->where('tool_category_id', $tool_category_id)
                    ->update([
                        'tool_category_id' => null
                    ]);
                $message = 'Xóa Variation thành công.';
            }
            $status = 'success';
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $status = 'error';
            $message = 'Xảy ra lỗi. Hãy thử lại.';
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($status, $message);
    }

    public function editInfoFulfills($request)
    {
        $status = 'error';
        $rq = $request->all();
        \DB::beginTransaction();
        try {
            $fulfill_id = $rq['fulfill_id'];
            $woo_order_id = $rq['woo_order_id'];
            $data = $rq;
            unset($data['fulfill_id']);
            unset($data['woo_order_id']);
            unset($data['_token']);
            $result = \DB::table('fulfills')->where('id', $fulfill_id)->update($data);
            \DB::table('woo_orders')->where('id', $woo_order_id)->update($data);
            if ($result)
            {
                $status = 'success';
                $message = 'Cập nhật thông tin order thành công.';
            } else {
                $message = 'Xảy ra lỗi. Không thể cập nhật thông tin order bây giờ. Mời bạn tải lại trang và thử lại.';
            }

            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $status = 'error';
            $message = 'Xảy ra lỗi. Hãy thử lại.';
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($status, $message);
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

    /*Scrap web */
    public function viewFromCreateTemplate()
    {
        $data = infoShop();
        $lst_web = website();
        $lst_auto_webs = website_auto();
        $stores = \DB::table('woo_infos')
            ->select('id', 'name', 'url', 'consumer_key', 'consumer_secret')
            ->get()->toArray();
        return view('/admin/scrap/view_create_template')
            ->with(compact('data', 'stores', 'lst_web', 'lst_auto_webs'));
    }
    /*End Scrap web*/

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

    public function scanAgainTemplate($woo_template_id)
    {
        \DB::beginTransaction();
        try {
            $message_status = 'error';
            $message = '';
            $id = $woo_template_id;
            $result = \DB::table('woo_templates')->where('id', $id)->update([
                'status' => 0,
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            if ($result) {
                $message_status = 'success';
                $message = 'Re scan template thành công.';
            } else {
                $message = 'Re scan template thất bại. Mời bạn thử lại';
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
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $message_status = 'error';
            $message = '';
            $name_driver = trim($rq['name_driver']);
            $template_id = trim($rq['template_id']);
            $category_id = trim($rq['category_id']);
            $category_name = trim($rq['category_name']);
            $woo_category_id = trim($rq['woo_category_id']);
            $store_id = trim($rq['store_id']);
            $path_driver = env("GOOGLE_PRODUCTS") . '/' . trim($rq['path_driver']);
            $woo_folder_driver_id = \DB::table('woo_folder_drivers')->insertGetId([
                'name' => $name_driver,
                'path' => $path_driver,
                'template_id' => $template_id,
                'category_id' => $category_id,
                'category_name' => $category_name,
                'woo_category_id' => $woo_category_id,
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
                            'category_id' => $category_id,
                            'category_name' => $category_name,
                            'woo_category_id' => $woo_category_id,
                            'tag_name' => strtolower($product['filename']),
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
        \DB::beginTransaction();
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

    public function deleteAllTemplate($woo_template_id)
    {
        \DB::beginTransaction();
        try {
            $alert = 'error';
            $exists = \DB::table('woo_templates')
                ->select('template_id', 'store_id', 'template_path')
                ->where('id', $woo_template_id)
                ->first();
            if ($exists == null) {
                $message = 'Không tồn tại Template này trên hệ thống. Mời bạn thử lại.';
            } else {
                $files_local = \DB::table('woo_variations')
                    ->select('id', 'variation_path')
                    ->where([
                        ['woo_template_id', '=', $woo_template_id],
                        ['template_id', '=', $exists->template_id],
                        ['store_id', '=', $exists->store_id]
                    ])
                    ->get()->toArray();
                $action = true;
                $variation_id = array();
                foreach ($files_local as $file) {
                    $file_path = $file->variation_path;
                    if (\File::exists($file_path)) {
                        if (!\File::delete($file_path)) {
                            $action = false;
                        }
                    }
                    $variation_id[] = $file->id;
                }
                $template_path = $exists->template_path;
                if (\File::exists($template_path)) {
                    if (!\File::delete($template_path)) $action = false;
                    if (!\File::deleteDirectory(dirname($template_path))) $action = false;
                }
                $result = \DB::table('woo_variations')->whereIn('id', $variation_id)->delete();
                $r = \DB::table('woo_templates')->where('id', $woo_template_id)->delete();
                if ($result && $r) {
                    $alert = 'success';
                    $message = 'Xóa trong database thành công.';
                    if (!$action) {
                        $message .= ' Nhưng không thể xóa hết file local. Mời bạn xóa tay';
                    }
                } else {
                    $message = ' Xảy ra lỗi không thể xóa template. Database woo_variations va woo_templates .Mời bạn thử lại sau';
                }
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            logfile($e->getMessage());
            echo $e->getMessage();
        }
        return redirect('woo-get-template')->with($alert, $message);
    }

    public function deleteAllProductTemplate($woo_template_id, $type)
    {
        \DB::beginTransaction();
        try {
            $alert = 'error';
            $message = '';
            $exists = \DB::table('woo_templates')
                ->select('id', 'template_id', 'store_id')->where('id', $woo_template_id)->first();
            if ($exists != NULL) {
                $where = [
                    ['template_id', '=', $exists->template_id],
                    ['store_id', '=', $exists->store_id]
                ];
                if ($type == 0) //up driver
                {
                    // Delete all product not create in tool
                    $deleted = \DB::table('woo_product_drivers')->where($where)->where('status', 0)->delete();
                    $deleted = \DB::table('woo_folder_drivers')->where($where)->delete();
                    $check_exist = \DB::table('woo_product_drivers')->where($where)->count();
                    if ($check_exist > 0) {
                        $update = \DB::table('woo_product_drivers')->where($where)->update(['status' => 23]);
                        if ($update) {
                            $alert = 'success';
                            $message = 'Thành công. Tất cả sản phẩm thuộc template này sẽ được xóa vào thời gian tới.';
                            \DB::table('woo_templates')->where('id', $woo_template_id)->update(['status' => 23]);
                        } else {
                            $message = 'Xảy ra lỗi. Không thể cập nhật sản phẩm đã up lên store vào danh sách phải xóa.';
                        }
                    } else {
                        $alert = 'success';
                        $message = 'Thành công. Tất cả sản phẩm thuộc template này sẽ được xóa vào thời gian tới.';
                        \DB::table('woo_templates')->where('id', $woo_template_id)->update(['status' => 23]);
                    }
                } else if ($type == 1) // scrap website
                {
                    // Delete all product not create in tool
                    $deleted = \DB::table('scrap_products')->where($where)->where('status', 0)->delete();
                    $check_exist = \DB::table('scrap_products')->where($where)->count();
                    if ($check_exist > 0) {
                        $update = \DB::table('scrap_products')->where($where)->where('status', 1)->update(['status' => 23]);
                        if ($update) {
                            $alert = 'success';
                            $message = 'Thành công. Tất cả sản phẩm thuộc template này sẽ được xóa vào thời gian tới.';
                            \DB::table('woo_templates')->where('id', $woo_template_id)->update(['status' => 23]);
                        } else {
                            $message = 'Xảy ra lỗi. Không thể cập nhật sản phẩm đã up lên store vào danh sách phải xóa.';
                        }
                    } else {
                        $alert = 'success';
                        $message = 'Thành công. Tất cả sản phẩm thuộc template này sẽ được xóa vào thời gian tới.';
                        \DB::table('woo_templates')->where('id', $woo_template_id)->update(['status' => 23]);
                    }
                }
            } else {
                $alert = 'error';
                $message = ' Xảy ra lỗi không thể xóa sản phẩm. Mời bạn thử lại sau';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            logfile($e->getMessage());
            echo $e->getMessage();
        }
        return redirect('woo-get-template')->with($alert, $message);
    }

    public function ajaxPutConvertVariation($request)
    {
        \DB::beginTransaction();
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
                        $tmp_variation_data = explode("\n", $json_data);
                        if (sizeof($tmp_variation_data) > 0) {
                            foreach ($tmp_variation_data as $variation_data) {
                                if (trim($variation_data) == '') {
                                    continue;
                                }
                                $tmp = explode(';', $variation_data);
                                $variation_old_slug = mb_ereg_replace("[.]", '-', $tmp[0]);
                                $data[] = [
                                    'variation_change_id' => $variation_change_id,
                                    'variation_old' => trim($tmp[0]),
                                    'variation_compare' => trim($tmp[1]),
                                    'variation_new' => trim($tmp[2]),
                                    'variation_sku' => trim($tmp[3]),
                                    'variation_old_slug' => str_replace(" ", "-",
                                        mb_ereg_replace("([^\w\s\d\~,;\[\]\(\).-])", '', strtolower(trim($variation_old_slug)))),
                                    'created_at' => date("Y-m-d H:i:s"),
                                    'updated_at' => date("Y-m-d H:i:s")
                                ];
                            }
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
        \DB::beginTransaction();
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

    public function deletedCategories()
    {
        $data = infoShop();
        $stores = \DB::table('woo_infos')->select('id', 'name')->get()->toArray();
        return view('addon/deleted_categories', compact('stores', 'data'));
    }

    public function actionDeletedCategories($request)
    {
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $store_id = $rq['store_id'];
            \DB::table('woo_categories')
                ->where('store_id', $store_id)
                ->where('status', '<', 2)
                ->update(['status' => 23]);
            $alert = 'success';
            $message = 'Lệnh xóa đã được kích hoạt thành công. Hệ thống sẽ xóa toàn bộ categories trong thời gian tới.';
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $alert = 'error';
            logfile($e->getMessage());
            $message = 'Lệnh xóa thất bại. Mời bạn thử lại' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return redirect('deleted-categories')->with($alert, $message);
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

    /*Keyword Categories*/
    public function editKeywordCategory($woo_category_id)
    {
        $data = array();
        $category = \DB::table('woo_categories as wot')
            ->leftjoin('ads_keywords as k_word', 'wot.id', '=', 'k_word.woo_category_id')
            ->select(
                'wot.id', 'wot.woo_category_id', 'wot.name as category_name',
                'k_word.keyword'
            )
            ->where('wot.id', $woo_category_id)
            ->get()->toArray();
        $cat = array();
        $info = array();
        foreach ($category as $item) {
            if (sizeof($info) == 0) {
                $info = [
                    'category_name' => $item->category_name,
                    'id' => $item->id
                ];
                $cat['info'] = $info;
            }
            $cat['keyword'][] = $item->keyword;
        }
        return view('keyword/lst_keywords')->with(compact('data', 'cat'));
    }

    public function listCategories()
    {
        $data = array();
        $categories = $this->getCategory();
        $woo_infos = \DB::table('woo_infos')->select('id','name')->get()->toArray();
        return view('/keyword/list_categories', compact('data', 'categories', 'woo_infos'));
    }

    private function getCategory()
    {
        $categories = \DB::table('woo_categories as wot')
            ->leftjoin('woo_infos', 'wot.store_id', '=', 'woo_infos.id')
            ->select(
                'wot.id', 'wot.woo_category_id', 'wot.name as category_name',
                'woo_infos.name as store_name', 'woo_infos.id as store_id'
            )
            ->where('wot.status', 0)
            ->orderBy('wot.store_id')
            ->orderBy('wot.id','DESC')
            ->get()->toArray();
        return $categories;
    }

    public function listVariationCategory()
    {
        $data = array();
        $categories = \DB::table('tool_categories as tot')
            ->select('tot.id','tot.name as category_name','tot.note')->orderBy('tot.name','DESC')->get()->toArray();
        $variations = \DB::table('variations')
            ->leftjoin('tool_categories', 'variations.tool_category_id', '=', 'tool_categories.id')
            ->select(
                'variations.id', 'variations.variation_name', 'variations.price', 'variations.variation_sku',
                'variations.variation_real_name', 'variations.factory_sku',
                'tool_categories.name as category_name'
            )
            ->orderBy('id', 'DESC')
            ->get()->toArray();
        return view('/admin/woo/list_category_variation', compact(
            'data', 'categories', 'variations'));
    }

    public function updateVariation()
    {
        $alert = 'error';
        \DB::beginTransaction();
        try {
            $variations = \DB::table('woo_orders')->select('variation_detail')->distinct('variation_detail')->get()->toArray();
            $variation_exist = \DB::table('variations')->pluck('variation_name')->toArray();
            $data_variations = array();
            // so sanh
            foreach ($variations as $item)
            {
                if (!in_array($item->variation_detail, $variation_exist)) {
                    $data_variations[] = [
                        'variation_name' => $item->variation_detail,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                }
            }
            if (sizeof($data_variations) > 0) {
                $result = \DB::table('variations')->insert($data_variations);
                if ($result) {
                    $alert = 'success';
                    $message = 'Đã tạo ' . sizeof($data_variations) . ' variations thành công';
                } else {
                    $message = 'Xảy ra lỗi. Không thể tạo variations lúc này. Mời bạn thử lại';
                }
            } else {
                $message = 'Đã hết variation để tạo mới';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $alert = 'error';
            logfile($e->getMessage());
            $message = 'Tạo variations thất bại. Mời bạn thử lại' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($alert, $message);
    }

    public function addNewToolCategory($request)
    {
        $alert = 'error';
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $tool_category_name = $rq['tool_category_name'];
            $note = $rq['note'];
            $check = \DB::table('tool_categories')->select('id')->where('name',$tool_category_name)->count();
            if ($check > 0)
            {
                $message = 'Đã tồn tại category : '.$tool_category_name.' trong hệ thống rồi. Mời bạn chọn tên khác';
            } else {
                $result = \DB::table('tool_categories')->insert([
                    'name' => $tool_category_name,
                    'note' => htmlentities(str_replace("\n", ". ", $note)),
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                if ($result) {
                    $alert = 'success';
                    $message = 'Tạo mới thành công category: '.$tool_category_name;
                } else {
                    $message = 'Xảy ra lỗi. Mời bạn thử lại';
                }
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            logfile($e->getMessage());
            $message = 'Cập nhật variations thất bại. Mời bạn thử lại' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($alert, $message);
    }

    public function editToolCategory($request)
    {
        $alert = 'error';
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $tool_category_id = $rq['tool_category_id'];
            $tool_category_name = $rq['tool_category_name'];
            $note = $rq['note'];
            $result = \DB::table('tool_categories')->where('id',$tool_category_id)->update([
                'name' => $tool_category_name,
                'note' => htmlentities(str_replace("\n", ". ", $note)),
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            if ($result) {
                $alert = 'success';
                $message = 'Sửa thành công category: '.$tool_category_name;
            } else {
                $message = 'Xảy ra lỗi. Mời bạn thử lại';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            logfile($e->getMessage());
            $message = 'Xảy ra lỗi nội bộ. Mời bạn thử lại' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($alert, $message);
    }

    public function NewTemplateCategory($request)
    {
        $uid = $this->checkAuth();
        $alert = 'error';
        if ($uid) {
            $rq = $request->all();
            $data = $rq['data_title'];
            $tool_category_id = trim($rq['tool_category_id']);
            if ($tool_category_id != '') {
                \DB::beginTransaction();
                try {
                    $tmps = explode(";", $data);
                    $insert_data = array();
                    $i = 1;
                    foreach ($tmps as $tmp) {
                        if ($tmp != '') {
                            $title_data = explode('-', $tmp);
                            $insert_data[] = [
                                'key_title' => trim($title_data[0]),
                                'title' => trim($title_data[1]),
                                'fixed' => (trim($title_data[2]) != '.') ? trim($title_data[2]) : '',
                                'tool_category_id' => $tool_category_id,
                                'sort' => $i,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                            $i++;
                        }
                    }
                    if (sizeof($insert_data) > 0) {
                        // xóa dữ liệu cũ trước
                        \DB::table('template_excels')->where('tool_category_id', $tool_category_id)->delete();
                        $result = \DB::table('template_excels')->insert($insert_data);
                        if ($result) {
                            $alert = 'success';
                            $message = 'Đã tạo template cho category này thành công';
                        } else {
                            $message = 'Xảy ra lỗi khi cập nhật vào database. Mời bạn thử lại.';
                        }
                    } else {
                        $message = 'Bạn cần phải điền ít nhất là 1 trường để có thể thay đổi';
                    }
                    \DB::commit(); // if there was no errors, your query will be executed
                } catch (\Exception $e) {
                    logfile($e->getMessage());
                    $message = 'Xảy ra lỗi nội bộ. Mời bạn thử lại' . $e->getMessage();
                    \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
                }
            } else {
                $message = 'Bạn cần phải điền ít nhất là 1 trường để có thể thay đổi';
            }
        } else {
            $message = 'Đã hết phiên đăng nhập. Mời bạn đăng nhập và thử lại';
        }
        return \Redirect::back()->with($alert, $message);
    }

    public function deleteToolCategory($tool_category_id)
    {
        $alert = 'error';
        \DB::beginTransaction();
        try {
            $where = [
                ['tool_category_id', '=', $tool_category_id]
            ];
            $check = \DB::table('variations')->select('id')->where($where)->count();
            if ($check > 0)
            {
                \DB::table('variations')->where($where)->update([
                    'tool_category_id' => null
                ]);
            }
            $result = \DB::table('tool_categories')->where('id',$tool_category_id)->delete();
            if ($result) {
                $alert = 'success';
                $message = 'Xoá thành công category';
            } else {
                $message = 'Xảy ra lỗi. Mời bạn thử lại';
            }

            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            logfile($e->getMessage());
            $message = 'Xảy ra lỗi nội bộ. Mời bạn thử lại' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($alert, $message);
    }

    public function listTemplateCategory()
    {
        $data = infoShop();
        $categories = \DB::table('tool_categories')->select('id', 'name', 'type_fulfill_id', 'exclude_text')->get()->toArray();
        $list_type = typeFulfill();
        return view('admin/list_tool_category',compact('data','categories','list_type'));
    }

    public function makeTemplateCategory($tool_category_id)
    {
        $data = infoShop();
        $lst_titles = getListTitle();
        $template_excel = \DB::table('template_excels')
            ->select('key_title','title','fixed')
            ->where('tool_category_id',$tool_category_id)
            ->orderBy('sort', 'ASC')
            ->get()->toArray();
        $excel_titles = array();
        foreach ($template_excel as $item)
        {
            $excel_titles[$item->key_title] = json_decode(json_encode($item, true), true);
        }
        return view('popup/make_template_category',
            compact('data', 'lst_titles','tool_category_id','excel_titles'));
    }

    public function editVariations($request)
    {
        $alert = 'error';
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            if ($rq['price'] != '')
            {
                $data_update = [
                    'variation_real_name' => $rq['variation_real_name'],
                    'price' => $rq['price'],
                    'variation_sku' => ($rq['variation_sku'] != '')? $rq['variation_sku'] : null,
                    'factory_sku' => ($rq['factory_sku'] != '')? $rq['factory_sku'] : null,
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                $result = \DB::table('variations')->where('id', $rq['id'])->update($data_update);
                if ($result)
                {
                    $alert = 'success';
                    $message = 'Cập nhật thành công variation';
                } else {
                    $message = 'Xảy ra lỗi. Cập nhật thất bại variation. Mời bạn tải lại trang và thử lại.';
                }
            } else {
                $message = 'Xảy ra lỗi. Bạn nên nhập giá tiền gốc vào để cập nhật variation';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            logfile($e->getMessage());
            $message = 'Cập nhật variations thất bại. Mời bạn thử lại' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($alert, $message);
    }

    public function deleteWooCategory($woo_category_id)
    {
        \DB::beginTransaction();
        try {
            //xóa hết keyword
            \DB::table('ads_keywords')->where('woo_category_id',$woo_category_id)->delete();
            \DB::table('woo_categories')->where('id',$woo_category_id)->delete();

            $alert = 'success';
            $message = 'Đã xóa cateogory thành công.';
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $alert = 'error';
            logfile($e->getMessage());
            $message = 'Xóa cateogory thất bại. Mời bạn thử lại' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return redirect('/list-categories')->with($alert, $message);
    }

    public function showKeywordCategory($request)
    {
        $uid = $this->checkAuth();
        if ($uid) {
            $rq = $request->all();
            $cat_id = $rq['cat_id'];
            $cat_name = $rq['cat_name'];
            $keywords = \DB::table('ads_keywords')->where('woo_category_id', $cat_id)->pluck('keyword')->toArray();
            if (sizeof($keywords) > 0) {
                $list_keyword = implode(",", $keywords);
                $str = 'Đã tải xong từ khóa của category : <b>' . $cat_name . '</b>';
            } else {
                $list_keyword = '';
                $str = 'Category <b>' . $cat_name . '</b> chưa được nạp từ khóa. Mời bạn nạp ngay.';
            }
            $alert = 'success';
            $message = '<small class="green-text">' . $str . '</small>';
        } else {
            $alert = 'error';
            $message = '<small class="red-text"> Đã hết phiên. Mời bạn đăng nhập và thử lại.</span>';
        }
        return response()->json([
            'message' => $message,
            'result' => $alert,
            'cat_id' => $cat_id,
            'cat_name' => $cat_name,
            'list_keyword' => $list_keyword
        ]);
    }

    public function addListKeyword($request)
    {
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $woo_category_id = $rq['id'];
            $lst_keyword = explode(",", $rq['lst_keyword']);
            $data = array();
            foreach ($lst_keyword as $keyword) {
                $data[] = [
                    'woo_category_id' => $woo_category_id,
                    'keyword' => $keyword,
                    'status' => 0,
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ];
            }
            //xóa hết keyword cũ
            $result = \DB::table('ads_keywords')->where('woo_category_id', $woo_category_id)->delete();
            \DB::table('ads_keywords')->insert($data);
            $alert = 'success';
            $message = 'Đã thêm từ khóa thành công.';
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $alert = 'error';
            logfile($e->getMessage());
            $message = 'Thêm từ khóa thất bại. Mời bạn thử lại' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return redirect('list-categories')->with($alert, $message);
    }

    public function getStoreFeed()
    {
        $data = array();
        $stores = \DB::table('woo_infos')->select('id', 'name')->get()->toArray();
        $categories = \DB::table('woo_categories')
            ->select('id', 'name','store_id')
            ->where('status',0)
            ->orderBy('name','ASC')
            ->get()->toArray();
        $stores = json_decode(json_encode($stores), true);
        $categories = json_decode(json_encode($categories), true);

        //list category dang duoc yeu cau cap nhat
        $lst_requests = \DB::table('check_categories as c_cate')
            ->join('woo_categories', 'c_cate.category_id', '=', 'woo_categories.id')
            ->join('woo_infos', 'c_cate.store_id', '=', 'woo_infos.id')
            ->select(
                'c_cate.id as check_category_id', 'c_cate.status','c_cate.created_at', 'c_cate.woo_category_id',
                'c_cate.store_id',
                'woo_categories.name as category_name',
                'woo_infos.name as store_name'
            )
            ->orderBy('c_cate.id','DESC')
            ->get()->toArray();
        //lay danh sach feed dang check lại
        $feeds_all = \DB::table('feed_products')->select('id','store_id','category_name','status')->get()->toArray();
        $feeds = array();
        foreach ($feeds_all as $item)
        {
            $feeds[$item->store_id][$item->category_name]['all'][] = $item->id;
            if ($item->status == 1)
            {
                $feeds[$item->store_id][$item->category_name]['done'][] = $item->id;
            }
        }

        //lấy danh sách tạo google feed
        $google_feeds = \DB::table('google_feeds')->select('*')->orderBy('id','DESC')->get()->toArray();
        return view('keyword/list_store',compact('data','stores', 'categories','lst_requests','feeds',
            'google_feeds'));
    }

    public function processFeedStore($request)
    {
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $store_id = $rq['store_id'];
            $lst_category = $rq['lst_category'];
            switch ($request->input('action')) {
                case 'feed':
                    // Save model
                    $result = $this->makeFileFeed($store_id, $lst_category);
                    break;

                case 'check_again':
                    $result = $this->checkAgainProduct($store_id, $lst_category);
                    // Redirect to advanced edit
                    break;
            }
            $alert = $result[0];
            $message = $result[1];
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $alert = 'error';
            logfile($e->getMessage());
            $message = 'Thêm từ khóa thất bại. Mời bạn thử lại' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return redirect('get-store')->with($alert, $message);
    }

    private function makeFileFeed($store_id, $category_id)
    {
        $alert = 'error';
        $category_name = '';
        $stores = \DB::table('woo_infos')->select('name')->where('id',$store_id)->first();
        $store_name = $stores->name;
        if ($category_id == 'all')
        {
            $where = [
                ['store_id', '=', $store_id],
                ['status', '=', 1],
            ];
            $category_name = 'All';
        } else {
            $where = [
                ['category_id', '=', $category_id]
            ];
            $cat = \DB::table('woo_categories')->select('name')->where('id',$category_id)->first();
            $category_name = $cat->name;
        }

        $feeds = \DB::table('feed_products')->select('*')->where($where)
            ->orderBy('id','ASC')
            ->orderBy('category_id','ASC')
            ->get()->toArray();
        if (sizeof($feeds) > 0)
        {
            $lst_stores = array();
            $stores = \DB::table('woo_infos')->select('*')->get()->toArray();
            foreach ($stores as $store)
            {
                $lst_stores[$store->id] = $store->name;
            }
            // loc het tu khoa cua category vao 1 nhom array theo key
            $categories = array();
            $lst_categories = \DB::table('ads_keywords')->select('*')->get()->toArray();
            foreach ($lst_categories as $category)
            {
                $categories[$category->woo_category_id][] = $category->keyword;
            }
            // so sánh nếu trùng category thì nhét từ khóa vào feed
            $lst_feeds = array();
            $ar_tmp_category = array();
            $i = 0;
            foreach ($feeds as $feed)
            {
                // thêm từ khóa ra sale vào feed title
                $title = '';
                if (array_key_exists($feed->category_id, $categories))
                {
                    $ar_tmp_category[$feed->category_id][$i] = $i;
                    if (sizeof($ar_tmp_category[$feed->category_id]) == sizeof($categories[$feed->category_id]))
                    {
                        $ar_tmp_category = array();
                        $i = 0;
                    }
                    $title = ucwords($categories[$feed->category_id][$i]);
                    $i++;
                }
                $tmp = explode(" ",trim($feed->woo_product_name));
                $custom_lable_2 = $tmp[sizeof($tmp) - 1];
                $lst_feeds[$feed->id] = [
                    'id' => $feed->woo_product_id,
                    'title' => trim($title).' '.trim($feed->woo_product_name),
                    'description' => trim($title).' '.trim($feed->description),
                    'link' => $feed->woo_slug,
                    'image_link' => $feed->woo_image,
                    'availability' => 'in stock',
                    'price' => '',
                    'sale_price' => '',
                    'google_product_category' => '',
                    'brand' => $lst_stores[$feed->store_id],
                    'gtin' => '',
                    'mpn' => 'yes',
                    'identifier_exists' => 'false',
                    'condition' => 'New',
                    'color' => '',
                    'size' => '',
                    'age_group' => 'Adult',
                    'gender' => 'Unisex',
                    'product_type' => $feed->category_name,
                    'custom_label_0' => $feed->category_name,
                    'custom_label_1' => $custom_lable_2,
                    'custom_label_2' => '',
                    'custom_label_3' => '',
                    'custom_label_4' => ''
                ];
            }
            $name_file = 'feeds_'.date("mdYHis");
            $check = createFileExcel($name_file, $lst_feeds, storage_path('feed'), 'google_feed');
            if ($check)
            {
                $path = storage_path('feed/'.$name_file.'.csv');
                $data = [
                    'file_name' => $name_file.'.csv',
                    'store_name' => $store_name,
                    'category_name' => $category_name,
                    'path' => $path,
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                $result = \DB::table('google_feeds')->insert($data);
                if ($result)
                {
                    $alert = 'success';
                    $message = 'Tạo thành công feed google.';
                } else {
                    $message = 'Tạo được file CSV nhưng không thể lưu vào database. Xóa CSV và tạo lại.';
                    if (\File::exists($path)) {
                        \File::delete($path);
                    }
                }
            } else {
                $message = 'Không thể tạo được file CSV. Mời bạn thử lại';
            }
        } else {
            $message = 'Category này chưa được tạo feed. Mời bạn chọn nút "check product" trước';
        }
        return array($alert, $message);
    }

    private function checkAgainProduct($store_id, $category_id)
    {
        $alert = 'success';
        $message = '';
        if ($category_id == 'all')
        {
            $where = [
                ['store_id', '=', $store_id],
                ['status', '=', 0],
            ];
        } else {
            $where = [
                ['id', '=', $category_id]
            ];
        }
        $checking = \DB::table('check_categories')->where('status',0)->pluck('category_id')->toArray();
        $lists = \DB::table('woo_categories')
            ->select('id', 'woo_category_id', 'name')
            ->where($where)->get()->toArray();
        if (sizeof($lists) > 0)
        {
            $data = array();
            $str = '';
            foreach ($lists as $value)
            {
                if (!in_array($value->id,$checking))
                {
                    $data[] = [
                        'category_id' => $value->id,
                        'woo_category_id' => $value->woo_category_id,
                        'store_id' => $store_id,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                } else {
                    $str .= $value->name.", ";
                }
            }
            if (sizeof($data) > 0)
            {
                $insert = \DB::table('check_categories')->insert($data);
                if (strlen($str) > 0)
                {
                    $message = 'Category: '. rtrim(trim($str),',').' đang được kiểm tra. Tất cả category còn lại sẽ được kiểm tra lại.';
                } else {
                    $message = 'Yêu cầu kiểm tra của bạn đã được thực hiện.';
                }

            } else {
                $message = 'Tất cả category bạn chọn đều đang trong quá trình kiểm tra lại. Không cần thực hiện hành động này nữa';
            }
        }
        return array($alert, $message);
    }

    // xóa file google feed
    public function feedDeleteFile($google_feed_id)
    {
        $google_feed = \DB::table('google_feeds')->select('file_name','path')->where('id',$google_feed_id)->first();
        if ($google_feed != NULL)
        {
            $path = $google_feed->path;
            //xóa file trên hệ thống server
            if (\File::exists($path)) {
                \File::delete($path);
            }
            $result = \DB::table('google_feeds')->where('id',$google_feed_id)->delete();
            if ($result)
            {
                $alert = 'success';
                $message = 'Xóa thành công google feed : '.$google_feed->file_name;
            } else {
                $alert = 'error';
                $message = 'Xảy ra lỗi khi xóa file : '.$google_feed->file_name.'. Mời bạn thử lại';
            }
        } else {
            $alert = 'error';
            $message = 'Không tồn tại google feed này. Mời bạn kiểm tra lại';
        }
        return redirect('get-store')->with($alert, $message);
    }

    //download file google feed
    public function feedGetFile($google_feed_id)
    {
        $google_feed = \DB::table('google_feeds')->select('file_name','path')->where('id',$google_feed_id)->first();
        if ($google_feed != NULL)
        {
            return response()->download($google_feed->path);
        } else {
            $alert = 'error';
            $message = 'Không tồn tại google feed này. Mời bạn kiểm tra lại';
            return redirect('get-store')->with($alert, $message);
        }
    }

    // fulfill order
    private function preDataFulfill()
    {
        $data_fulfills = array();
        $order_status = order_status();
        // lấy danh sách design id
        $lst_design_id = \DB::table('woo_orders')
            ->where('status', env('STATUS_WORKING_DONE'))
            ->whereIn('order_status', $order_status)
            ->distinct()->pluck('design_id')->toArray();
        if (sizeof($lst_design_id) > 0) {
            $designs = \DB::table('workings')
                ->leftjoin('designs', 'workings.design_id', '=', 'designs.id')
                ->leftjoin('working_files', function ($join) {
                    $join->on('working_files.working_id', '=', 'workings.id');
                })
                ->leftjoin('tool_categories as cat', 'designs.tool_category_id','=','cat.id')
                ->select(
                    'workings.id as working_id', 'workings.store_id',
                    'designs.id as design_id', 'designs.sku', 'designs.variation',
                    'working_files.name', 'working_files.path', 'working_files.thumb',
                    'cat.type_fulfill_id', 'cat.exclude_text'
                )
                ->whereIn('design_id', $lst_design_id)
                ->where('designs.status','>=', env('STATUS_WORKING_DONE'))
                ->where('working_files.is_mockup', 1)
                ->get()->toArray();
            if (sizeof($designs) > 0) {
                $woo_orders = \DB::table('woo_orders')
                    ->leftjoin('file_fulfills as ff','woo_orders.id','=', 'ff.woo_order_id')
                    ->select(
                        'woo_orders.id as woo_order_id','woo_orders.number', 'woo_orders.transaction_id',
                        'woo_orders.fullname', 'woo_orders.email', 'woo_orders.first_name', 'woo_orders.last_name',
                        'woo_orders.address', 'woo_orders.city', 'woo_orders.state', 'woo_orders.country',
                        'woo_orders.postcode', 'woo_orders.phone', 'woo_orders.customer_note',
                        'woo_orders.product_name', 'woo_orders.design_id', 'woo_orders.variation_detail',
                        'woo_orders.quantity', 'woo_orders.price', 'woo_orders.shipping_cost', 'woo_orders.product_id',
                        'woo_orders.woo_info_id', 'woo_orders.sku',
                        'ff.tool_category_id', 'ff.web_path_file', 'ff.web_path_folder'
                    )
                    ->where('woo_orders.status', env('STATUS_WORKING_DONE'))
                    ->whereIn('woo_orders.order_status', $order_status)
                    ->get()->toArray();
                $data_fulfills = $this->sortDataFulfill($designs, $woo_orders);
            }
        } else {
            $alert = 'success';
            $message = '-- Đã hết new order để fulfill';
        }
        return $data_fulfills;
    }

    private function preDataFulfillScan($list_woo_order_id)
    {
        $data_fulfills = array();
        // lấy danh sách design id
        $lst_design_id = \DB::table('woo_orders')->whereIn('id',$list_woo_order_id)->distinct()->pluck('design_id')->toArray();
        if (sizeof($lst_design_id) > 0) {
            $designs = \DB::table('workings')
                ->leftjoin('designs', 'workings.design_id', '=', 'designs.id')
                ->leftjoin('working_files', function ($join) {
                    $join->on('working_files.working_id', '=', 'workings.id');
                })
                ->leftjoin('tool_categories as cat', 'designs.tool_category_id','=','cat.id')
                ->select(
                    'workings.id as working_id', 'workings.store_id',
                    'designs.id as design_id', 'designs.sku', 'designs.variation',
                    'working_files.name', 'working_files.path', 'working_files.thumb',
                    'cat.type_fulfill_id', 'cat.exclude_text'
                )
                ->whereIn('design_id', $lst_design_id)
                ->where('designs.status','>=', env('STATUS_WORKING_DONE'))
                ->where('working_files.is_mockup', 1)
                ->get()->toArray();

            if (sizeof($designs) > 0) {
                $woo_orders = \DB::table('woo_orders')
                    ->leftjoin('file_fulfills as ff','woo_orders.id','=', 'ff.woo_order_id')
                    ->select(
                        'woo_orders.id as woo_order_id','woo_orders.number', 'woo_orders.transaction_id',
                        'woo_orders.fullname', 'woo_orders.email', 'woo_orders.first_name', 'woo_orders.last_name',
                        'woo_orders.address', 'woo_orders.city', 'woo_orders.state', 'woo_orders.country',
                        'woo_orders.postcode', 'woo_orders.phone', 'woo_orders.customer_note',
                        'woo_orders.product_name', 'woo_orders.design_id', 'woo_orders.variation_detail',
                        'woo_orders.quantity', 'woo_orders.price', 'woo_orders.shipping_cost', 'woo_orders.product_id',
                        'woo_orders.woo_info_id', 'woo_orders.sku',
                        'ff.tool_category_id', 'ff.web_path_file', 'ff.web_path_folder'
                    )
                    ->whereIn('woo_orders.id',$list_woo_order_id)->get()->toArray();
                $data_fulfills = $this->sortDataFulfill($designs, $woo_orders);
            }
        }
        return $data_fulfills;
    }

    private function getDesignUrl($woo_order_id,$type_fulfill_id, $web_path_file, $web_path_folder)
    {
        $path = '';
        $special = $woo_order_id*9+5;
        // one file
        if($type_fulfill_id == '1')
        {
            $path = $web_path_file;
        } elseif ($type_fulfill_id == '2') { // multi file
            $path = $web_path_file;
        } elseif ($type_fulfill_id == '3') { // folder
            $path = 'api/dir-fulfill/'.basename($web_path_folder).'_'.$special;
        }
        return $path;
    }

    private function sortDataFulfill($designs, $woo_orders)
    {
        $data_fulfills = array();
        //lấy toàn bộ variations ra ngoài
        $tmp_variations = \DB::table('variations')
            ->select('variation_name', 'variation_real_name','price','variation_sku','tool_category_id','factory_sku')
            ->get()->toArray();
        //phân loại variations
        $lst_variations = array();
        foreach ($tmp_variations as $variation)
        {
            $key = $variation->variation_name;
            $lst_variations[$key] = json_decode(json_encode($variation, true),true);
        }

        $tmp_designs = array();
        foreach ($designs as $design) {
            $tmp_designs[$design->design_id] = json_decode(json_encode($design, true), true);
        }
        // thu thap data lưu vào database

        $order_fulfills = array();
        $design_url = '';
        foreach ($woo_orders as $order) {
            $works = '';
            $key_order = $order->variation_detail;
            $sku = $order->sku;
            $base_price = 0;
            $factory_sku = '';
            $tool_category_id = 0;
            if (array_key_exists($key_order, $lst_variations))
            {
                $vari = $lst_variations[$key_order];
                $sku = trim($order->sku.$vari['variation_sku']);
                $base_price = $vari['price'];
                $variation_detail = ($vari['variation_real_name'] != '')? $vari['variation_real_name'] : $vari['variation_name'];
                $factory_sku = $vari['factory_sku'];
                $tool_category_id = $vari['tool_category_id'];
            }
            if (array_key_exists($order->design_id, $tmp_designs)) {
                $works = $tmp_designs[$order->design_id];
                $type_fulfill_id = $works['type_fulfill_id'];
                $exclude_text = $works['exclude_text'];
                $web_path_file = $order->web_path_file;
                $web_path_folder = $order->web_path_folder;

                // nếu chưa tồn tại order fulfill lần nào
                if (!in_array($order->woo_order_id, $order_fulfills))
                {
                    $order_fulfills[] = $order->woo_order_id;
                    if ($type_fulfill_id == 2)
                    {
                        $design_url = env('URL_LOCAL').$this->getDesignUrl($order->woo_order_id, $type_fulfill_id, $web_path_file, $web_path_folder).$exclude_text;
                    } else {
                        $design_url = env('URL_LOCAL').$this->getDesignUrl($order->woo_order_id, $type_fulfill_id, $web_path_file, $web_path_folder);
                    }
                } else { // nếu đã tồn tại order trước đó rồi
                    // nếu là multi file
                    if ($type_fulfill_id == 2)
                    {
                        $design_url .= env('URL_LOCAL'). $this->getDesignUrl($order->woo_order_id, $type_fulfill_id, $web_path_file, $web_path_folder).$exclude_text;
                    } else {
                        $design_url = env('URL_LOCAL').$this->getDesignUrl($order->woo_order_id, $type_fulfill_id, $web_path_file, $web_path_folder);
                    }
                }

                $data_fulfills[$tool_category_id][$order->woo_order_id] = [
                    'order_id' => $order->woo_order_id,
                    'order_number' => $order->number,
                    'transaction_id' => $order->transaction_id,
                    'currency' => '$',
                    'full_name' => $order->fullname,
                    'email' => $order->email,
                    'first_name' => $order->first_name,
                    'last_name' => $order->last_name,
                    'address' => $order->address,
                    'city' => $order->city,
                    'state' => $order->state,
                    'country' => $order->country,
                    'postcode' => $order->postcode,
                    'phone' => $order->phone,
                    'shipping' => '',
                    'customer_note' => $order->customer_note,
                    'variation_detail' => $variation_detail,
                    'product_name' => $order->product_name,
                    'sku' => $sku,
                    'design_id' => $order->design_id,
                    'size' => $order->variation_detail,
                    'quantity' => $order->quantity,
                    'color' => '',
                    'base_price' => $base_price,
                    'item_price' => $order->price,
                    'shipping_cost' => $order->shipping_cost,
                    'factory_sku' => $factory_sku,

                    'exact_art_work' => '',
                    'back_inscription' => '',
                    'memo' => '',
                    'design_position' => '',
                    'design_url' => $design_url,

                    'product_image' => env('URL_LOCAL').$works['thumb'],
                    'product_id' => $order->product_id,
                    'working_id' => $works['working_id'],
                    'store_id' => $order->woo_info_id,
                ];
            }
        }
        return $data_fulfills;
    }

    private function preDataTemplateExcel()
    {
        $excels = \DB::table('template_excels as tec')
            ->leftjoin('tool_categories','tec.tool_category_id', '=', 'tool_categories.id')
            ->select(
                'tec.key_title','tec.title', 'tec.fixed',
                'tool_categories.name as tool_category_name', 'tool_categories.id as tool_category_id'
            )
            ->orderBy('tec.sort','ASC')
            ->get()->toArray();
        $tmp_excels = array();
        if (sizeof($excels) > 0)
        {
            foreach ($excels as $item)
            {
                $tmp_excels[$item->tool_category_id]['info'] = [
                    'tool_category_name' => $item->tool_category_name,
                    'tool_category_id' => $item->tool_category_id
                ];
                $tmp_excels[$item->tool_category_id]['sort'][$item->key_title] = [
                    'key_title' => $item->key_title,
                    'title' => $item->title,
                    'fixed' => $item->fixed,
                ];
            }
        }
        return $tmp_excels;
    }

    private function actionDataFulfill($data, $excels, $type, $excel_fulfill_id = null)
    {
        $files = array();
        $message = '';
        //lọc file excel trước
        foreach ($excels as $tool_category_id => $item_excel)
        {
            // kiểm tra xem có category không
            if(array_key_exists($tool_category_id, $data))
            {
                // lưu thông tin cơ bản tên và id của category từ excel vào files
                $files[$tool_category_id]['info'] = $item_excel['info'];

                // bắt đầu lặp data woo order cần fulfill
                foreach ($data[$tool_category_id] as $woo_order_id => $item_data)
                {
                    // refresh dữ liệu tạm để sang dữ liệu mới.
                    $tmp = array();
                    //Sắp xếp thứ tự order theo thứ tự file excel
                    foreach ($item_excel['sort'] as $key_title => $template_excel)
                    {
                        $value_key_tittle = (isset($item_data[$key_title])) ? $item_data[$key_title] : '';
                        // nếu fixed cứng dữ liệu từ trước thì gửi ra dữ liệu fix cứng
                        $tmp[$template_excel['title']] = ($template_excel['fixed'] != '') ? $template_excel['fixed'] : $value_key_tittle;
                    }
                    $files[$tool_category_id]['data'][$woo_order_id] = $tmp;
                }
            }
        }
        $time = date("Ymd_Hms");
        //nếu là tạo mới lần đầu tiên
        if ($type == env('FULFILL_TYPE_CREATE'))
        {
            foreach ($files as $item)
            {
                $name_file = str_replace(" ", "_", strtolower($item['info']['tool_category_name'])).'_'.$time;
                $make_excel = createFileExcel($name_file, $item['data'], storage_path('feed'), $name_file);
                if ($make_excel)
                {
                    $excel_fulfill_id = \DB::table('excel_fulfills')->insertGetId([
                        'date_fulfill' => $name_file.'.csv',
                        'tool_category_id' => $item['info']['tool_category_id'],
                        'path' => storage_path('feed'),
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    $list_woo_order_id = array_keys($item['data']);
                    // cập nhật excel fulfill id vào woo orders
                    $result = \DB::table('woo_orders')->whereIn('id',$list_woo_order_id)->update([
                        'status' => env('STATUS_WORKING_MOVE'),
                        'excel_fulfill_id' => $excel_fulfill_id,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
                    if ($result && $excel_fulfill_id)
                    {
                        $message .= 'Tạo file excel '.$item['info']['tool_category_name'].' thành công'."<br>";
                    } else {
                        $message .= 'Tạo file excel '.$item['info']['tool_category_name'].' thất bại'."<br>";
                    }
                }
            }

        } else if($type == env('FULFILL_TYPE_UPDATE')) {
            foreach ($files as $item)
            {
                $excel_fulfil_data = \DB::table('excel_fulfills')->select('id','date_fulfill', 'path')
                    ->where('id', $excel_fulfill_id)
                    ->where('tool_category_id', $item['info']['tool_category_id'])
                    ->first();
                if ($excel_fulfil_data != NULL)
                {
                    $name_file = str_replace(" ", "_", strtolower($item['info']['tool_category_name'])).'_'.$time;
                    $make_excel = createFileExcel($name_file, $item['data'], storage_path('feed'), $name_file);
                    if ($make_excel)
                    {
                        \File::delete($excel_fulfil_data->path.'/'.$excel_fulfil_data->date_fulfill);
                        $result = \DB::table('excel_fulfills')->where('id', $excel_fulfill_id)->update([
                            'date_fulfill' => $name_file.'.csv',
                            'updated_at' => date("Y-m-d H:i:s")
                        ]);
                        if ($result)
                        {
                            $message .= 'Tạo lại file excel '.$item['info']['tool_category_name'].' thành công';
                        } else {
                            $message .= 'Tạo lại file excel '.$item['info']['tool_category_name'].' thất bại';
                        }
                    }
                }
            }
        }
        return $message;
    }

    public function actionFulfillNow()
    {
        $alert = 'error';
        \DB::beginTransaction();
        try {
            // lấy data để save ra file excel
            $data = $this->preDataFulfill();
            if (sizeof($data) > 0) {
                // lấy cấu trúc file excel
                $excels = $this->preDataTemplateExcel();
                if (sizeof($excels) > 0) {
                    // đủ dữ liệu yêu cầu thì bắt đầu tạo file excel
                    $message = $this->actionDataFulfill($data, $excels, env('FULFILL_TYPE_CREATE'));
                } else {
                    $message = 'Bạn chưa chọn cấu trúc để export file fulfill. Mời bạn chọn ở mục "Category Template Fulfill" trước khi thực hiện hành động này';
                }
            } else {
                $alert = 'success';
                $message = 'Đã hết đơn hàng để fulfill';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            logfile($e->getMessage());
            $message = 'Xảy ra lỗi nội bộ. Mời bạn thử lại' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($alert, $message);
    }

    public function fulfillRescanFile($excel_fulfill_id)
    {
        $alert = 'error';
        $message = '';
        \DB::beginTransaction();
        try {
            $list_woo_order_id = \DB::table('woo_orders')->where('excel_fulfill_id', $excel_fulfill_id)->pluck('id')->toArray();
            if (sizeof($list_woo_order_id) > 0) {
                // lấy data để save ra file excel
                $data = $this->preDataFulfillScan($list_woo_order_id);
                if (sizeof($data) > 0) {
                    // lấy cấu trúc file excel
                    $excels = $this->preDataTemplateExcel();
                    if (sizeof($excels) > 0) {
                        // đủ dữ liệu yêu cầu thì bắt đầu tạo file excel
                        $message = $this->actionDataFulfill($data, $excels, env('FULFILL_TYPE_UPDATE'), $excel_fulfill_id);
                    } else {
                        $message = 'Bạn chưa chọn cấu trúc để export file fulfill. Mời bạn chọn ở mục "Category Template Fulfill" trước khi thực hiện hành động này';
                    }
                } else {
                    $alert = 'success';
                    $message = 'Không có order nào thuộc file fulfill này. Mời bạn kiểm tra lại.';
                }
            } else {
                $message = 'Không có order nào thuộc file fulfill này.';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            logfile($e->getMessage());
            $message = 'Xảy ra lỗi nội bộ. Mời bạn thử lại' . $e->getMessage();
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($alert, $message);
    }

    public function fulfilGetFile($excel_fulfill_id)
    {
        $excel_fulfill = \DB::table('excel_fulfills')->select('date_fulfill','path')->where('id',$excel_fulfill_id)->first();
        if ($excel_fulfill != NULL)
        {
            $path = $excel_fulfill->path.'/'.$excel_fulfill->date_fulfill;
            return response()->download($path);
        } else {
            $alert = 'error';
            $message = 'Không tồn tại file fulfill này. Mời bạn kiểm tra lại.';
            return redirect('fulfill-category')->with($alert, $message);
        }
    }

    public function fulfillCategory()
    {
        $data = infoShop();
        $fulfills = \DB::table('excel_fulfills as exf')
            ->join('tool_categories', 'exf.tool_category_id', '=', 'tool_categories.id')
            ->join('woo_orders', 'exf.id', '=', 'woo_orders.excel_fulfill_id')
            ->leftjoin('trackings','trackings.order_id', '=', 'woo_orders.number')
            ->select(
                'exf.id', 'exf.date_fulfill', 'exf.path', 'exf.status', 'exf.created_at', 'exf.updated_at',
                'tool_categories.name',
                DB::raw("count(woo_orders.excel_fulfill_id) as count"),
                DB::raw("count(trackings.id) as count_tracking")
            )
            ->groupBy('woo_orders.excel_fulfill_id')
            ->orderBy('exf.created_at', 'DESC')
            ->limit(10)
            ->get()->toArray();
        return view('/admin/fulfill_category', compact('data','fulfills'));
    }

    public function updateToolCategory($request)
    {
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $design_id = $rq['design_id'];
            $tool_category_id = $rq['tool_category_id'];
            $result = \DB::table('designs')->where('id',$design_id)->update(['tool_category_id' => $tool_category_id]);
            if ($result)
            {
                $alert = 'success';
                $message = 'Cập nhật category thành công';
            } else {
                $alert = 'error';
                $message = "Cập nhật cateogory thất bại. Mời bạn thử lại";
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $alert = 'error';
            $message = "Xảy ra lỗi nội bộ. Mời bạn thử lại";
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($alert, $message);
    }

    public function workingChangeVariation($request)
    {
        \DB::beginTransaction();
        try {
            $rq = $request->all();
            $design_id = $rq['design_id'];
            $variation_name = trim($rq['variation_name']);
            // lay thong tin design hien tai
            $design_old = \DB::table('designs')->select('id', 'sku', 'variation')->where('id', $design_id)->first();
            if ($design_old != NULL) {
                $design_exist = \DB::table('designs')->select('id')
                    ->where('sku', $design_old->sku)
                    ->where('variation', $variation_name)
                    ->first();
                // nếu tồn tại design giống yêu cầu
                if ($design_exist != NULL) {
                    $new_design_id = $design_exist->id;
                    $working_files = \DB::table('workings')
                        ->leftjoin('working_files as wkf', 'workings.id', '=', 'wkf.working_id')
                        ->select('wkf.id as working_file_id', 'wkf.name', 'wkf.path', 'wkf.thumb', 'workings.id as working_id')
                        ->where('workings.design_id', $design_id)
                        ->get()->toArray();
                    $delete_files = array();
                    $delete_working_files = array();
                    $delete_workings = array();
                    foreach ($working_files as $file) {
                        $delete_files[] = storage_path($file->path . $file->name);
                        $delete_files[] = storage_path($file->thumb);
                        $delete_working_files[$file->working_file_id] = $file->working_file_id;
                        $delete_workings[$file->working_id] = $file->working_id;
                    }
                    if (sizeof($delete_working_files) > 0) {
                        $result = \DB::table('working_files')->whereIn('id', $delete_working_files)->delete();
                        if ($result) {
                            \DB::table('workings')->whereIn('id', $delete_workings)->delete();
                            \DB::table('designs')->where('id', $design_id)->delete();
                            \File::delete($delete_files);
                        }
                    }
                } else { // nếu không tồn tại design giống yêu cầu.
                    \DB::table('designs')->where('id', $design_id)->update(['variation' => $variation_name]);
                    $new_design_id = $design_id;
                }
                //cập nhật woo_orders
                $update_woo_orders = [
                    'design_id' => $new_design_id,
                    'variation_detail' => $variation_name,
                    'variation_full_detail' => $variation_name . '-;-;-',
                    'detail' => $variation_name . '-;-;-'
                ];
                \DB::table('woo_orders')->where('design_id', $design_id)->update($update_woo_orders);
                $alert = 'success';
                $message = 'Cập nhật thành công variations.';
            } else {
                $alert = 'error';
                $message = 'Không tồn tại design này trên hệ thống. Mời bạn kiểm tra lại';
            }
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            $alert = 'error';
            $message = "Xảy ra lỗi nội bộ. Mời bạn thử lại";
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return \Redirect::back()->with($alert, $message);
    }

    public function fulfillDetail($excel_fulfill_id)
    {
        $data = infoShop();
        $lists = \DB::table('woo_orders')
            ->leftjoin('trackings as t','woo_orders.number', '=', 't.order_id')
            ->select(
                't.id as tracking_id','woo_orders.number as order_id', 't.tracking_number', 't.status',
                't.shipping_method', 't.time_upload', 't.note'
            )
            ->where('excel_fulfill_id', $excel_fulfill_id)
            ->orderBy('woo_orders.id')
            ->get()->toArray();
        return view('addon.view_tracking_detail', compact('data', 'lists'));
    }
    /*End Admin + QC*/
}
