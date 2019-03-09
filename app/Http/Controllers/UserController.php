<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    public function index()
    {
//        return view('welcome');

        //neu la admin
        if (1)
        {
            return view('/admin/dashboard');
        }
        else
        {
            return view('/staff/dashboard');
        }
    }
    //
}
