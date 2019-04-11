<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WooInfo extends Model
{
    public $timestamps = true;
    protected $table='woo_infos';

    // Triển khai code logic
    public static function saveStore()
    {
        $request = request();
        $woo = new WooInfo();
        $woo->name = $request->store_name;
        $woo->url = $request->store_url;
        $woo->email = $request->email;
        $woo->sku = $request->sku;
        $woo->consumer_key = $request->consumer_key;
        $woo->consumer_secret = $request->consumer_secret;
        $woo->status = 0;
        if ($woo->save()) {
            \Session::flash('success', 'Successfully add new store!');
        } else {
            \Session::flash('error', 'Error! Please try again an other time!');
        }
    }
}
