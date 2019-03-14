<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Api extends Model
{
    public function log($str)
    {
        \Log::info($str);
    }

    /*Create new order*/
    public function creatOrder($data , $woo_id)
    {
        $db = array();
        $this->log('=====================CREATE NEW ORDER=======================');
        if (sizeof($data['line_items']) > 0 )
        {
            $this->log('Store '.$woo_id.' has new '.sizeof($data['line_items']).' order item.');
            foreach ($data['line_items'] as $key => $value)
            {
                $str = "";
                foreach ($value['meta_data'] as $item)
                {
                    $str .= $item['key']." : ".$item['value']." ,\n";
                }
                $db[] = [
                    'woo_info_id' => $woo_id,
                    'order_id' => $data['id'],
                    'number' => $data['number'],
                    'order_status' => $data['status'],
                    'product_id' => $value['product_id'],
                    'product_name' => $value['name'],
                    'quantity' => $value['quantity'],
                    'email' => $data['billing']['email'],
                    'detail' => trim(htmlentities($str)),
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ];
            }
        }
        if (sizeof($db) > 0)
        {
            \DB::beginTransaction();
            try {
                \DB::table('woo_orders')->insert($db);
                $return = true;
                $save = "Save to database successfully";
                \DB::commit(); // if there was no errors, your query will be executed
            } catch (\Exception $e) {
                $return = false;
                $save = "[Error] Save to database error.";
                \DB::rollback(); // either it won't execute any statements and rollback your database to previous state
            }
            $this->log($save."\n");
        }
    }
}
