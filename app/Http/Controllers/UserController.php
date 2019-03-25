<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\User;
use DB;
use Session;
use App\Working;

class UserController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    public function index()
    {
//        return view('welcome');

        if (Auth::user()) {
            $user = \DB::table('users')
                ->select('name','level')
                ->where('id',\Auth::user()->id)
                ->first();
            $work = new Working();
            switch ($user->level) {
                case env('SADMIN'):
                    return $work->adminDashboard();
                    break;
                case env('ADMIN'):
                    return $work->adminDashboard();
                    break;
                case env('WORKER'):
                    return $work->staffDashboard();
                    break;
                case env('QC'):
                    return $work->qcDashboard();
                    break;
                default:
                    \Session::flash('success', 'Đăng nhập thành công. Vui lòng liên hệ quản lý để phân quyền.');
                    return view('/home');
            }
        }
    }
    //
}
