<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Api;
use File;

use Illuminate\Support\Facades\Storage;

class ApiController extends Controller
{

    /*WOOCOMMERCE API*/
    public function newOrder(Request $request)
    {
        /*Get Header Request*/
        $header = getallheaders();
        $webhook_head = [
            'x-wc-webhook-event' => trim($header['X-Wc-Webhook-Event']),
            'x-wc-webhook-resource' => trim($header['X-Wc-Webhook-Resource']),
            'x-wc-webhook-source' => trim($header['X-Wc-Webhook-Source'])
        ];
        $woo_id = $this->getStoreInfo($webhook_head);
//        \Log::info($woo_id);

        /*Get data Request*/
        $data = @file_get_contents('php://input');
        $data = json_decode( $data, true);
//        \Log::info($data);

        /*Send data to processing*/
        if (sizeof($data) > 0 && $woo_id !== false)
        {
            $api = new Api();
            $api->creatOrder($data,$woo_id);
        }
    }

    public function getStoreInfo($webhook_head)
    {
        $url = substr($webhook_head['x-wc-webhook-source'],0,-1);
        $store = DB::table('woo_infos')
            ->where('url',$url)
            ->pluck('id')
            ->toArray();
        $woo_id = false;
        if (sizeof($store) > 0)
        {
            $woo_id = $store[0];
        }
        return $woo_id;
    }

    public function checkPaymentAgain()
    {
        $api = new Api();
        return $api->checkPaymentAgain();
    }

    public function testNewOrder($filename)
    {
        $files = File::get(storage_path('file/'.$filename.'.json'));
        $data = json_decode($files,true);
        $api = new Api();
        $webhook_source = 'https://sportgear247.com';
        $store = DB::table('woo_infos')
            ->where('url',$webhook_source)
            ->pluck('id')
            ->toArray();
        $api->creatOrder($data,$store[0]);
    }

    public function updateProduct(Request $request)
    {
        logfile('Nhận được thông báo update product');
        /*Get Header Request*/
        $header = getallheaders();
        $webhook_head = [
            'x-wc-webhook-event' => trim($header['X-Wc-Webhook-Event']),
            'x-wc-webhook-resource' => trim($header['X-Wc-Webhook-Resource']),
            'x-wc-webhook-source' => trim($header['X-Wc-Webhook-Source'])
        ];
        $woo_id = $this->getStoreInfo($webhook_head);
//        \Log::info($woo_id);

//        \Log::info($data);
        /*Get data Request*/
        $data = @file_get_contents('php://input');
        $data = json_decode( $data, true);
        /*Send data to processing*/
        if (sizeof($data) > 0 && $woo_id !== false)
        {
            $api = new Api();
            $api->updateProduct($data,$woo_id);
        } else {
            logfile('Không có thông tin gì về update');
        }
    }

    public function testUpdateProduct($filename)
    {
        $files = File::get(storage_path('file/'.$filename.'.json'));
        $data = json_decode($files,true);
        $api = new Api();
        $webhook_source = 'https://zaraon.com';
        $store = DB::table('woo_infos')
            ->where('url',$webhook_source)
            ->pluck('id')
            ->toArray();
        $api->updateProduct($data,$store[0]);
    }

    private function getInfoShop($request)
    {
        /*Get Header Request*/
        $header = getallheaders();
        $webhook_head = [
            'x-wc-webhook-event' => trim($header['X-Wc-Webhook-Event']),
            'x-wc-webhook-resource' => trim($header['X-Wc-Webhook-Resource']),
            'x-wc-webhook-source' => trim($header['X-Wc-Webhook-Source'])
        ];
        $url = substr($webhook_head['x-wc-webhook-source'],0,-1);
        $store = DB::table('woo_infos')
            ->where('url',$url)
            ->pluck('id')
            ->toArray();
        $woo_id = false;
        if (sizeof($store) > 0)
        {
            $woo_id = $store[0];
        }
        \Log::info($woo_id);
        return $woo_id;
    }
    /*END WOOCOMMERCE API*/
}

