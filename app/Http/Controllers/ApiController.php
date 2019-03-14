<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Api;
use File;

use Illuminate\Support\Facades\Storage;

class ApiController extends Controller
{
    public function newOrder(Request $request)
    {
        $payload = @file_get_contents('php://input');
        $payload = json_decode( $payload, true);
        \Log::info(json_encode( $payload));
    }

    public function testNewOrder()
    {
        $files = File::get(storage_path('file/2.json'));
        $data = json_decode($files,true);
        $api = new Api();
        $webhook_source = 'https://sportgear247.com';
        $store = DB::table('woo_infos')
            ->where('url',$webhook_source)
            ->pluck('id')
            ->toArray();
        $api->creatOrder($data,$store[0]);
    }
}

