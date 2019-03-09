<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Session;
use Redirect;
use DB;
use App\User; // this to add User Model
use App\WooInfo;
use Validator;

class WooController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

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

    /*Webhooks*/
    public function webhooks()
    {
        return view('admin/woo/webhooks');
    }

    public function updateOrder()
    {
        return "aaaa";
    }
    /*End Webhooks*/
}
