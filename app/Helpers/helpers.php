<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

function logfile($str)
{
    \Log::info($str);
}

function getMessage($message)
{
    return '<ul class="collection">' . $message . '</ul>';
}

function getErrorMessage($message)
{
    return '<li class="red lighten-3 collection-item">' . $message . '</li>';
}

function getSuccessMessage($message)
{
    return '<li class="green lighten-1 collection-item">' . $message . '</li>';
}

function getTypeProduct($type)
{
    switch ($type) {
        case 'App':
            $t = 1;
            break;
        case '1Design':
            $t = 2;
            break;
        default:
            $t = 0;
            break;
    }
    return $t;
}

function statusType($type)
{
    switch ($type) {
        case 0 :
            $class = 'blue lighten-3';
            $st = 'Customize';
            break;
        case 1 :
            $class = 'indigo lighten-3';
            $st = 'App';
            break;
        case 2 :
            $class = 'amber lighten-3';
            $st = 'Normal';
            break;
    }
    $str = '<div class="center ' . $class . '">' . $st . '</div>';
    return $str;
}

function statusPayment($status, $payment)
{
    $class = '';
    switch ($status) {
        case 'processing' :
            $class = 'green lighten-3';
            break;
        case 'on-hold' :
            $class = 'deep-orange lighten-3';
            break;
        case 'cancelled' :
            $class = 'grey lighten-2';
            break;
        case 'pending' :
            $class = 'blue-grey lighten-3';
            break;
    }
    $str = '<div class="center ' . $class . '" title="' . $status . '">' . $payment . '</div>';
    return $str;
}

function sendEmail($email_from, $pass, $host, $port, $security, $email_to, $title, $body, $file = null)
{
    // Create the Transport
    $transport = (new Swift_SmtpTransport($host, $port, $security))
        ->setUsername($email_from)
        ->setPassword($pass);

    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);

    // Create a message
    if ($file == null) {
        $message = (new Swift_Message($title))
            ->setFrom([$email_from => 'Support Care'])
            ->setTo([$email_to])
            ->setBody($body);
    } else {
        $message = (new Swift_Message($title))
            ->setFrom([$email_from => 'Support Care'])
            ->setTo([$email_to])
            ->attach(Swift_Attachment::fromPath($file))
            ->setBody($body);
    }

    // Send the message
    $result = $mailer->send($message);
    logfile("Sended: " . $email_from . " to " . $email_to . " with title: " . $title);
}

function statusJob($status, $redo, $reason)
{
    $class = '' . $status;
    $st = '' . $reason;
    if ($status == env('STATUS_WORKING_NEW')) {
        $class = 'blue lighten-3';
        $st = 'New';
    } else if ($status == env('STATUS_WORKING_CHECK')) {
        $class = 'amber lighten-3';
        $st = 'Check';
    } else if ($status == env('STATUS_WORKING_CUSTOMER')) {
        $class = 'purple lighten-3';
        $st = 'ReCheck';
    } else if ($status == env('STATUS_WORKING_DONE')) {
        $class = 'light-green lighten-3';
        $st = 'Done';
    } else if ($status == env('STATUS_WORKING_MOVE')) {
        $class = 'teal lighten-3';
        $st = 'Moving';
    } else if ($status == env('STATUS_NOTFULFILL')) {
        $class = 'deep-orange lighten-3';
        $st = 'Pending';
    } else if ($status == env('STATUS_UPLOADED')) {
        $class = 'green lighten-3';
        $st = 'Uploaded';
    } else if ($status == env('STATUS_SKIP')) {
        $class = 'indigo lighten-3';
        $st = 'App';
    } else if ($status == env('STATUS_PRODUCT_NORMAL')) {
        $class = 'brown lighten-3';
        $st = 'Normal';
    } else if ($status == env('STATUS_FINISH')) {
        $class = 'green accent-4';
        $st = 'Finish';
    }

    if ($redo == 1) {
        $str = '<div class="center ' . $class . '">' . $st . '-<span class="red">Redo</span></div>';
    } else {
        $str = '<div class="center ' . $class . '">' . $st . '</div>';
    }
    return $str;
}

function showTracking($tracking_number, $status)
{
    $class = '';
    $icon = '';
    $title = '';
    switch ($status) {
        case env('TRACK_NEW'):
            $class = 'blue-grey lighten-5';
            $icon = '';
            $title = 'NEW';
            break;
        case env('TRACK_NOTFOUND'):
            $class = 'grey lighten-3';
            $icon = '<i class="material-icons dp48">visibility_off</i>';
            $title = 'NOTFOUND';
            break;
        case env('TRACK_INTRANSIT'):
            $class = 'blue lighten-2';
            $icon = '<i class="material-icons dp48">trending_up</i>';
            $title = 'INTRANSIT';
            break;
        case env('TRACK_PICKUP'):
            $class = 'blue darken-4';
            $icon = '<i class="material-icons dp48">system_update_alt</i>';
            $title = 'PICKUP';
            break;
        case env('TRACK_UNDELIVERED'):
            $class = 'red lighten-1';
            $icon = '<i class="material-icons dp48">new_releases</i>';
            $title = 'UNDELIVERED';
            break;
        case env('TRACK_DELIVERED'):
            $class = ' green lighten-1';
            $icon = '<i class="material-icons dp48">done</i>';
            $title = 'DELIVERED';
            break;
        case env('TRACK_ALERT'):
            $class = 'orange lighten-1';
            $icon = '<i class="material-icons dp48">warning</i>';
            $title = 'ALERT';
            break;
        case env('TRACK_EXPIRED'):
            $class = 'brown lighten-1';
            $icon = '<i class="material-icons dp48">schedule</i>';
            $title = 'EXPIRED';
            break;
    }
    $str = '<a href="https://t.17track.net/en#nums='.$tracking_number.'" target="_blank" style="color: #555;" class="center ' . $class . '" 
    title="'.$title.'">' . $icon .'<span>'.$tracking_number . '</span></a>';
    return $str;
}

function thumb_c($path, $height, $name)
{
    return '<img src="' . $path . '" class="img-thumbnail" height="' . $height . '" title="' . $name . '"/>';
}

function thumb($path, $height, $name)
{
    return '<img src="' . $path . '?nocache=' . rand(1, 99) . '?hash=' . time() . $name . '" class=" img-thumbnail" height="' . $height . '" title="' . $name . '"/>';
}

function thumb_w($path, $width, $name)
{
    return '<img src="' . $path . '?nocache=' . rand(1, 99) . '?hash=' . time() . $name . '" class=" img-thumbnail" width="' . $width . '" title="' . $name . '"/>';
}

function genThumb($file, $path, $width_new)
{
    $size = getimagesize($path);
    $width = $size[0];
    $height = $size[1];
    $height_new = (int) $height/($width/$width_new);
    $name = env('DIR_THUMB').'thumb_'.date("YmdHis").'_'.$file;
    $smallthumbnailpath = public_path($name);
    $return = false;
    if(\File::copy($path, $smallthumbnailpath)) {
        $img = Image::make($smallthumbnailpath)->resize($width_new, $height_new, function ($constraint) {
            $constraint->aspectRatio();
        });
        if ($img->save($smallthumbnailpath))
        {
            $return = $name;
        }
    } else {
        $return = false;
    }
    return $return;
}

function compareTime($from, $to)
{
    $created = new Carbon($from);
    $now = new Carbon($to);
    $class = 'style="color:green;"';
    if ($created->diffInDays($now) >= 1) {
        $class = 'style="color:red;"';
    } else {
        if ($created->diffInHours($now) > 1) {
            $class = 'style="color:orange;"';
        }
    }
    return '<p ' . $class . '>' . $created->diffForHumans($now, ['options' => Carbon::NO_ZERO_DIFF]) . '</p>';

}

function notiSideBar($count)
{
    $badge = '';
    if ($count > 0) {
        $badge = '<span class="new badge">' . $count . '</span>';
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
function createDir($name, $parent_path = null)
{
    $name = trim($name);
    $return = false;
    $recursive = false; // Get subdirectories also?
    if (Storage::cloud()->makeDirectory($parent_path . '/' . $name)) {
        $dir = collect(Storage::cloud()->listContents($parent_path, $recursive))
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
    if ($path == '')
    {
        $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
            ->where('type', '=', 'dir')
            ->where('filename', '=', $name)
            ->first();
    } else {
        $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
            ->where('type', '=', 'dir')
            ->where('filename', '=', $name)
            ->where('path', '=', $path)
            ->first();
    }
    if ($check_before) {
        $return = true;
    }
    return $return;
}

function getDirExist($name, $path, $parent_path)
{
    $name = trim($name);
    $return = false;
    $recursive = false; // Get subdirectories also?
    if ($path == '')
    {
        $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
            ->where('type', '=', 'dir')
            ->where('filename', '=', $name)
            ->first();
    } else {
        $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
            ->where('type', '=', 'dir')
            ->where('filename', '=', $name)
            ->where('path', '=', $path)
            ->first();
    }
    if ($check_before) {
        $return = $check_before;
    }
    return $return;
}

function scanGoogleDir($path, $type)
{
    $return = false;
    $recursive = false; // Get subdirectories also?
    $check = collect(Storage::cloud()->listContents($path, $recursive))
        ->where('type', '=', $type)
        ->sortBy('name');
    return $check;
}

function scanFolder($path)
{
    $return = false;
    $recursive = false; // Get subdirectories also?
    $check = collect(Storage::cloud()->listContents($path, $recursive))
        ->where('type', '=', 'file');
    return $check;
}

function deleteDir($name, $parent_path = null)
{
    $return = false;
    $name = trim($name);
    $recursive = false; // Get subdirectories also?
    $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
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
        if (Storage::cloud()->move($check_before['path'], $path . '/' . $new_name)) {
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
        $new_name = (strlen($new_name) > 0) ? $new_name : $filename;
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

function getFile($filename, $path, $parent_path = null)
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

function checkFileExist($filename, $parent_path)
{
    $return = false;
    $name = trim($filename);
    $recursive = false; // Get subdirectories also?
    $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
        ->where('type', '=', 'file')
        ->where('name', '=', $filename)
        ->first();
    if ($check_before)
    {
        $return = true;
    }
    return $return;
}

/*END GOOGLE API*/

/*Ham hien thi thong tin shop*/
function infoShop()
{
    $data = array();
    $user = DB::table('users')
        ->select('name', 'level', 'id')
        ->where('id', \Auth::user()->id)
        ->first();
    $ar_qc = array(env('SADMIN'), env('ADMIN'), env('QC'));
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
    if (in_array($user->level, $ar_qc)) {
        $order_review = getOrderReview();
        $check_idea = getIdeaCheck();
        $idea_send_support = getIdeaDone();
        $data['private'] = [
            'order_review' => $order_review,
            'idea_send_support' => $idea_send_support,
            'check_idea' => $check_idea,
        ];
    } else if ($user->level == env('WORKER')) {
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
    return \DB::table('woo_orders')->where('status', env('STATUS_WORKING_NEW'))
        ->where('custom_status', env('STATUS_P_CUSTOM_PRODUCT'))
        ->count();
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
    return \DB::table('ideas')->where('status', env('STATUS_WORKING_NEW'))->count();
}

function getIdeaNewWorker($wid)
{
    return \DB::table('ideas')
        ->where([
            ['status', '=', env('STATUS_WORKING_NEW')],
            ['worker_id', '=', $wid]
        ])->count();
}

function getOrderNewWorker($wid)
{
    return \DB::table('workings')
        ->where([
            ['status', '=', env('STATUS_WORKING_NEW')],
            ['worker_id', '=', $wid]
        ])->count();
}

function getIdeaCheck()
{
    return \DB::table('ideas')->where('status', env('STATUS_WORKING_CHECK'))->count();
}

function getOrderReview()
{
    return \DB::table('workings')->where('status', env('STATUS_WORKING_CUSTOMER'))->count();
}

function getIdeaDone()
{
    return \DB::table('ideas')->where('status', env('STATUS_WORKING_CUSTOMER'))->count();
}
/*End Ham hien thi thong tin shop*/

/*
 * Tao folder moi*/
function makeFolder($path)
{
    $result = true;
    if (!File::exists($path))
    {
        umask(0);
        $result = File::makeDirectory($path, 0777, true);
    }
    return $result;
}

/* Tao file json moi */
function writeFileJson($path_file, $data)
{
    // Write File
    $newJsonString = json_encode($data, JSON_PRETTY_PRINT |JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $result = File::put($path_file, stripslashes($newJsonString),'public');
    return $result;
}

/* Đọc file json*/
function readFileJson($path_file)
{
    $jsonString = File::get($path_file);
    $data = json_decode($jsonString, true);
    return $data;
}
