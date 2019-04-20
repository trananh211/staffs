<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

function logfile($str)
{
    \Log::info($str);
}

function statusJob($status, $redo, $reason)
{
    $class = ''.$status;
    $st = ''.$reason;
    if ($status == env('STATUS_WORKING_NEW')) {
        $class = 'blue lighten-3';
        $st = 'New';
    } else if ($status == env('STATUS_WORKING_CHECK')) {
        $class = 'amber lighten-3';
        $st = 'Check';
    } else if ($status == env('STATUS_WORKING_CUSTOMER')) {
        $class = 'purple lighten-3';
        $st = 'ReCheck';
    } else if ($status >= env('STATUS_WORKING_DONE')) {
        $class = 'green lighten-3';
        $st = 'Done';
    }

    if ($redo == 1) {
        $str = '<div class="center ' . $class . '">' . $st . '-<span class="red">Redo</span></div>';
    } else {
        $str = '<div class="center ' . $class . '">' . $st . '</div>';
    }
    return $str;
}

function thumb($path, $height, $name)
{
    return '<img src="' . $path . '?nocache='.rand(1,99).'?hash=' . time().$name . '" class="materialboxed img-thumbnail" height="' . $height . '" title="' . $name . '"/>';
}

function thumb_w($path, $width, $name)
{
    return '<img src="' . $path . '?nocache='.rand(1,99).'?hash=' . time().$name . '" class="materialboxed img-thumbnail" width="' . $width . '" title="' . $name . '"/>';
}

function compareTime($from, $to)
{
    $created = new Carbon($from);
    $now = new Carbon($to);
    $class = 'style="color:green;"';
    if ($created->diffInDays($now) >= 1) {
        $class = 'style="color:red;"';
    } else {
        if ($created->diffInHours($now) > 1){
            $class = 'style="color:orange;"';
        }
    }
    return '<p '.$class.'>'. $created->diffForHumans($now, ['options' => Carbon::NO_ZERO_DIFF]) .'</p>';

}

function notiSideBar($count){
    $badge = '';
    if ($count > 0) {
        $badge = '<span class="new badge">'.$count.'</span>';
    }
    return $badge;
}

function sanitizer($file)
{
    // Remove anything which isn't a word, whitespace, number
// or any of the following caracters ~,;[]().
// If you don't need to handle multi-byte characters
// you can use preg_replace rather than mb_ereg_replace
    $file = mb_ereg_replace("([^\w\s\d\~,;\[\]\(\).])", '', $file);
// Remove any runs of periods (thanks falstro!)
    $file = mb_ereg_replace("([\.]{2,})", '', $file);
    return $file;
}

/*GOOGLE API*/
function createDir($name, $path = null)
{
    $name = trim($name);
    $return = false;
    $recursive = false; // Get subdirectories also?
    if (Storage::cloud()->makeDirectory($path . '/' . $name)) {
        $dir = collect(Storage::cloud()->listContents($path, $recursive))
            ->where('type', '=', 'dir')
            ->where('filename', '=', $name)
            ->sortBy('timestamp')
            ->last();
        $return = $dir['path'];
    }
    return $return;
}

function checkDirExist($name, $path, $parent_path)
{
    $name = trim($name);
    $return = false;
    $recursive = false; // Get subdirectories also?
    $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
        ->where('type', '=', 'dir')
        ->where('filename', '=', $name)
        ->where('path', '=', $path)
        ->first();
    if ($check_before) {
        $return = true;
    }
    return $return;
}

function deleteDir($name, $path = null)
{
    $return = false;
    $name = trim($name);
    $recursive = false; // Get subdirectories also?
    $check_before = collect(Storage::cloud()->listContents($path, $recursive))
        ->where('type', '=', 'dir')
        ->where('filename', '=', $name)
        ->first();
    if ($check_before) {
        if (Storage::cloud()->deleteDirectory($check_before['path'])) {
            $return = true;
        }
    }
    return $return;
}

function renameDir($new_name, $old_name, $path = null)
{
    $return = false;
    $new_name = trim($new_name);
    $old_name = trim($old_name);
    $recursive = false; // Get subdirectories also?
    $check_before = collect(Storage::cloud()->listContents($path, $recursive))
        ->where('type', '=', 'dir')
        ->where('filename', '=', $old_name)
        ->first();
    if ($check_before) {
        if (Storage::cloud()->move($check_before['path'], $path.'/'.$new_name)) {
            $dir = collect(Storage::cloud()->listContents($path, $recursive))
                ->where('type', '=', 'dir')
                ->where('filename', '=', $new_name)
                ->first();
            $return = $dir['path'];
        }
    }
    return $return;
}

function upFile($path_info, $path = null, $new_name = null)
{
    $return = false;
    if (\File::exists($path_info)) {
        $filename = pathinfo($path_info)['basename'];
        $contents = File::get($path_info);
        $new_name = (strlen($new_name) > 0)? $new_name : $filename;
        if (Storage::cloud()->put($path . '/' . $new_name, $contents)) {
            $recursive = false; // Get subdirectories also?
            $file = collect(Storage::cloud()->listContents($path, $recursive))
                ->where('type', '=', 'file')
                ->where('filename', '=', pathinfo($new_name, PATHINFO_FILENAME))
                ->where('extension', '=', pathinfo($new_name, PATHINFO_EXTENSION))
                ->sortBy('timestamp')
                ->last();
            $return = $file['path'];
        }
    }
    return $return;
}

function deleteFile($filename, $path, $parent_path = null)
{
    $return = false;
    $name = trim($filename);
    $recursive = false; // Get subdirectories also?
    $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
        ->where('type', '=', 'file')
        ->where('name', '=', $filename)
        ->where('path', '=', $path)
        ->first();
    if ($check_before) {
        if (Storage::cloud()->delete($check_before['path'])) {
            $return = true;
        }
    }
    return $return;
}

/*END GOOGLE API*/

/*Ham hien thi thong tin shop*/
function infoShop()
{
    $data = array();
    $user = DB::table('users')
        ->select('name','level','id')
        ->where('id',\Auth::user()->id)
        ->first();
    $ar_qc = array(env('SADMIN'),env('ADMIN'),env('QC'));
    $order_new = getNewOrder();
    $order_working = getworkingOrder();
    $order_checking = getCheckingOrder();

    $idea_new = getIdeaNew();
    $idea_check = getIdeaCheck();
    $data['pub'] = [
        'new' => $order_new,
        'working' => $order_working,
        'order_checking' => $order_checking,
        'idea_new' => $idea_new,
        'idea_check' => $idea_check
    ];
    /*Nếu là QC và Admin*/
    if (in_array($user->level, $ar_qc)){
        $order_review = getOrderReview();
        $check_idea = getIdeaCheck();
        $idea_send_support = getIdeaDone();
        $data['private'] = [
            'order_review' => $order_review,
            'idea_send_support' => $idea_send_support,
            'check_idea' => $check_idea,
        ];
    } else if($user->level == env('WORKER')) {
        $idea_new = getIdeaNewWorker($user->id);
        $order_new = getOrderNewWorker($user->id);
        $data['private'] = [
            'order_new' => $order_new,
            'idea_new' => $idea_new
        ];
    }
    return $data;
}

function getNewOrder()
{
    return \DB::table('woo_orders')->where('status', env('STATUS_WORKING_NEW'))->count();
}

function getworkingOrder()
{
    return \DB::table('workings')->where('status', env('STATUS_WORKING_NEW'))->count();
}

function getCheckingOrder()
{
    return \DB::table('workings')->where('status', env('STATUS_WORKING_CHECK'))->count();
}

function getIdeaNew()
{
    return \DB::table('ideas')->where('status',env('STATUS_WORKING_NEW'))->count();
}

function getIdeaNewWorker($wid)
{
    return \DB::table('ideas')
        ->where([
            ['status','=',env('STATUS_WORKING_NEW')],
            ['worker_id','=', $wid]
        ])->count();
}

function getOrderNewWorker($wid)
{
    return \DB::table('workings')
        ->where([
            ['status','=',env('STATUS_WORKING_NEW')],
            ['worker_id','=', $wid]
        ])->count();
}

function getIdeaCheck()
{
    return \DB::table('ideas')->where('status',env('STATUS_WORKING_CHECK'))->count();
}

function getOrderReview()
{
    return \DB::table('workings')->where('status',env('STATUS_WORKING_CUSTOMER'))->count();
}

function getIdeaDone()
{
    return \DB::table('ideas')->where('status',env('STATUS_WORKING_CUSTOMER'))->count();
}

/*End Ham hien thi thong tin shop*/
