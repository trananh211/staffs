<?php

namespace App\Http\Controllers;

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
        return view('admin/woo/connect');
    }

    public function doConnect()
    {
        $woo = new WooInfo();
        \DB::beginTransaction();
        try {
            $woo->saveStore();
            \DB::commit(); // if there was no errors, your query will be executed
        } catch (\Exception $e) {
            \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
        }
        return back();
    }

    public function listStore()
    {
        $stores = WooInfo::get();
        return view('admin/woo/list_store',['stores' => $stores]);
    }

    public function webhooks()
    {
        return view('admin/woo/webhooks');
    }
    /*End S Admin*/

    /*Staff */
    public function staffDashboard()
    {
        $work = new Working();
        return view('staff/staff',['lists' => $work->listOrder()]);
    }

    public function detailOrder($order_id)
    {
        $work = new Working();
        return view('staff/detail_order',['details' => $work->detailOrder($order_id)]);
    }

    public function staffGetJob()
    {
        $work = new Working();
        return $work->staffGetJob();
    }

    public function staffDoneJob($up_id)
    {
        return view('staff/staff_done',['up_id'=>$up_id]);
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
        return view('admin/checking',['lists' => $work->checking()]);
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
        return view('/admin/review_customer',['lists'=> $work->reviewCustomer()]);
    }

    public function eventQcDone(Request $request)
    {
        $work = new Working();
        return $work->eventQcDone($request);
    }

    public function supplier()
    {
        $work = new Working();
        return view('/admin/supplier',['lists'=> $work->supplier()]);
    }

    public function createNewJob()
    {
        $work = new Working();
        $users = $work->listWorker();
        return view('/admin/newjob')->with(compact('users'));
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
    /*End Admin + QC*/
}
