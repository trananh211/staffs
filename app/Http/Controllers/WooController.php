<?php

namespace App\Http\Controllers;

use App\Api;
use Illuminate\Http\Request;
use Auth;
use DB;
use App\User; // this to add User Model
use App\WooInfo;
use App\Working;
use Validator;

class WooController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /*S Admin*/
    public function connect()
    {
        $data = infoShop();
        return view('admin/woo/connect', compact('data'));
    }

    public function doConnect(Request $request)
    {
        $data = $request->all();
        $str = '';
        if (array_key_exists('id_store', $data)) {
            $woo_info = WooInfo::find($request->id_store);
            $str = "Cập nhật thông tin thành công";
        } else {
            $woo_info = new WooInfo();
            $str = "Tạo mới thông tin store thành công";
        }
        $woo_info->name = $request->name;
        $woo_info->url = $request->url;
        $woo_info->email = $request->email;
        $woo_info->password = $request->password;
        $woo_info->host = $request->host;
        $woo_info->port = $request->port;
        $woo_info->security = $request->security;
        $woo_info->consumer_key = $request->consumer_key;
        $woo_info->consumer_secret = $request->consumer_secret;
        $woo_info->sku = $request->sku;
        $woo_info->status = 0;
        $result = $woo_info->save();
        if ($result) {
            $status = 'success';
            $message = $str;
        } else {
            $status = 'error';
            $message = 'Lưu thông tin thất bại';
        }
        return back()->with($status, $message);
    }

    public function listStore()
    {
        $stores = WooInfo::get();
        $data = infoShop();
        return view('admin/woo/list_store', compact('stores', 'data'));
    }

    public function editStore($id_store)
    {
        $data = infoShop();
        $store = \DB::table('woo_infos')->where('id', $id_store)->get();
        return view('admin/edit_store', compact('data'));
    }

    public function webhooks()
    {
        $data = infoShop();
        return view('admin/woo/webhooks', compact('data'));
    }
    /*End S Admin*/

    /*Staff */
    public function staffDashboard()
    {
        $work = new Working();
        $lists = $work->listOrder();
        $data = infoShop();
        return view('staff/staff', compact('data', 'lists'));
    }

    public function detailOrder($order_id)
    {
        $work = new Working();
        return view('staff/detail_order', ['details' => $work->detailOrder($order_id)]);
    }

    public function staffGetJob()
    {
        $work = new Working();
        return $work->staffGetJob();
    }

    public function staffDoneJob($up_id)
    {
        return view('staff/staff_done', ['up_id' => $up_id]);
    }

    public function staffUpload()
    {
        $work = new Working();
        return $work->staffUpload();
    }

    public function action(Request $request)
    {
        $work = new Working();
        return $work->staffUpload($request);
    }

    public function uploadIdea(Request $request)
    {
        $work = new Working();
        return $work->uploadIdea($request);
    }

    public function doNewIdea()
    {
        $work = new Working();
        return $work->doNewIdea();
    }
    /*End Staff*/

    /*Admin + QC*/
    public function checking()
    {
        $work = new Working();
        return $work->checking();
    }

    public function working()
    {
        $work = new Working();
        return $work->working();
    }

    public function sendCustomer($order_id)
    {
        $work = new Working();
        return $work->sendCustomer($order_id);
    }

    public function redoDesigner(Request $request)
    {
        $work = new Working();
        return $work->redoDesigner($request);
    }

    public function reviewCustomer()
    {
        $work = new Working();
        return $work->reviewCustomer();
    }

    public function eventQcDone(Request $request)
    {
        $work = new Working();
        return $work->eventQcDone($request);
    }

    public function supplier()
    {
        $work = new Working();
        $lists = $work->supplier();
        $data = infoShop();
        return view('/admin/supplier', compact('lists', 'data'));
    }

    public function createNewJob()
    {
        $work = new Working();
        $users = $work->listWorker();
        $data = infoShop();
        return view('/admin/newjob')->with(compact('users', 'data'));
    }

    public function saveNewJob(Request $request)
    {
        $work = new Working();
        return $work->saveNewJob($request);
    }

    public function listIdea()
    {
        $work = new Working();
        return $work->listIdea();
    }

    public function listIdeaDone()
    {
        $work = new Working();
        return $work->listIdeaDone();
    }

    public function axIdeaSendQc(Request $request)
    {
        $work = new Working();
        return $work->axIdeaSendQc($request);
    }

    public function axRedoIdea(Request $request)
    {
        $work = new Working();
        return $work->axRedoIdea($request);
    }

    public function axUploadIdea(Request $request)
    {
        $work = new Working();
        return $work->axUploadIdea($request);
    }

    public function axTakeJob(Request $request)
    {
        $work = new Working();
        return $work->axTakeJob($request);
    }

    public function axDeleteLog(Request $request)
    {
        $work = new Working();
        return $work->axDeleteLog($request);
    }

    public function listAllOrder()
    {
        $work = new Working();
        return $work->listAllOrder();
    }

    public function listAllProduct()
    {
        $work = new Working();
        return $work->listAllProduct();
    }

    public function updateOrder(Request $request)
    {
        $api = new Api();
        return $api->updateOrder($request);
    }

    public function upDesignNormal(Request $request)
    {
//        $work = new Working();
//        return $work->upDesignNormal($request);
    }

    public function axSkipProduct(Request $request)
    {
        $work = new Working();
        return $work->axSkipProduct($request);
    }

    public function autoGenThumb()
    {
        $work = new Working();
        return $work->autoGenThumb();
    }
    /*End Admin + QC*/
}
