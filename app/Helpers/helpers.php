<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

function logfile($str)
{
    $datetime = Carbon::now('Asia/Ho_Chi_Minh');
//    \Log::info($datetime . '==> ' . $str);
    echo $datetime . '==> ' . $str."\n";
}

function logfile_system($str)
{
    $datetime = Carbon::now('Asia/Ho_Chi_Minh');
    $log_str = $datetime . '==> ' . $str;
//    \Log::channel('hasu')->info($log_str);
    echo $log_str."\n";
}

// sub day là ngày muốn dời về phía trước, 10 ngày trước, 15 ngày trước, 30 ngày trước
function getTimeAgo($subday)
{
    return Carbon::now()->subDays($subday)->toDateString();
}

function website()
{
    $website = [
        '1' => 'https://namestories.com',
        '4' => 'https://percre.com/?s=low+top&post_type=product',
        '5' => 'https://percre.com/?s=high+top&post_type=product',
        '6' => 'https://shoesnp.com/search?q=season%20boots',
        '7' => 'https://zolagifts.com/',
        '8' => 'https://zolagifts.com/',
        '9' => 'http://icefrogshoe.com/search?q=blanket',
        '15' => 'https://creationslaunch.com/search?q=high+top&type=product',
        '16' => 'https://creationslaunch.com/search?q=low+top&type=product',
        '17' => 'https://anzgiftshop.com/search?q=blanket',
        '18' => 'https://ble-store.com/search?q=B6L2AF01',
        '19' => 'http://icefrogshoe.com/search?q=B450',
        '20' => 'https://molofa.net/search?q=B750'
    ];
    return $website;
}

function website_auto()
{
    $website = [
        '1' => 'Not Flatform',
        '2' => 'Merchking',
        '3' => 'Esty'
    ];
    return $website;
}

function getListTitle()
{
    $common = [
        'order_id' => '12345',
        'order_number' => 'MLF-6868-USA',
        'transaction_id' => '1Xefdidcdf',
        'currency' => '$'
    ];
    $customer = [
        'full_name' => 'David Jame',
        'email' => 'david@gmail.com',
        'first_name' => 'Jame',
        'last_name' => 'David',
        'address' => '828 Gerlitz Road',
        'city' => 'Southwest',
        'state' => 'FL ',
        'country' => 'US',
        'postcode' => '32908',
        'phone' => '(321) 460-9218',
        'shipping' => '',
        'customer_note' => 'I wanna xyz'
    ];
    $product = [
        'variation_detail' => 'US6 (EU 41)',
        'variation_full_detail' => 'female-;-;-white-;-;-low_top_us9_eu40-;-;-I-AM-MOTIFIAH-;-;-',
        'product_image' => 'https://mol...',
        'product_name' => 'Jame Low Top',
        'sku' => 'JameZA003B41',
        'design_id' => '12345',
        'size' => 'size'
    ];
    $order = [
        'quantity' => '1',
        'color' => 'On Color',
        'base_price' => '20.99',
        'item_price' => '59.99',
        'shipping_cost' => '5.99',
    ];

    $others = [
        'exact_art_work' => '',
        'back_inscription' => '',
        'memo' => '',
        'design_position' => '',
        'design_url' => 'https://dropbox.com/...',
        'tracking_number' => 'LS123459KD',
        'factory_sku' => 'SHB'
    ];
    ksort($common);
    ksort($customer);
    ksort($product);
    ksort($order);
    ksort($others);
    $lists = [
        'Common' => $common,
        'Product' => $product,
        'Customer' => $customer,
        'Order' => $order,
        'Others' => $others
    ];
    return $lists;
}

function typeFulfill()
{
    $lists = [
        '1' => 'One File',
        '2' => 'Multi File',
        '3' => 'Folder'
    ];
    return $lists;
}


function dynamic_website()
{
    $website = [
        '1' => 'http://icefrogshoe.com/search?q=blanket'
    ];
    return $website;
}

function categories()
{
    $categories = [
        '2' => 'Wall Art',
        '3' => 'Wall Art',
        '4' => 'Shoes',
        '5' => 'Shoes',
        '6' => 'Boots'
    ];
    return $categories;
}

function getTemplateStatus()
{
    $template_status = [
        env('TEMPLATE_STATUS_KEEP_TITLE') => 'Keep tittle template',
        env('TEMPLATE_STATUS_REMOVE_TITLE') => 'Remove tittle template',
    ];
    return $template_status;
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
    if ($status == '' || $status == env('STATUS_WORKING_NEW')) {
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
            $title = 'NOT FOUND';
            break;
        case env('TRACK_INTRANSIT'):
            $class = 'blue lighten-2';
            $icon = '<i class="material-icons dp48">trending_up</i>';
            $title = 'IN TRANSIT';
            break;
        case env('TRACK_PICKUP'):
            $class = 'blue darken-4';
            $icon = '<i class="material-icons dp48">system_update_alt</i>';
            $title = 'PICK UP';
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
    $str = '<a href="https://t.17track.net/en#nums=' . $tracking_number . '" target="_blank" style="color: #555;" class="tooltipped center ' . $class . '" 
     data-position="top" data-delay="50" data-tooltip="' . $title . '">' . $icon . '<span>' . $tracking_number . '</span></a>';
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
    $height_new = (int)$height / ($width / $width_new);
    $name = env('DIR_THUMB') . 'thumb_' . date("YmdHis") . '_' . $file;
    $smallthumbnailpath = public_path($name);
    $return = false;
    if (\File::copy($path, $smallthumbnailpath)) {
        $img = Image::make($smallthumbnailpath)->resize($width_new, $height_new, function ($constraint) {
            $constraint->aspectRatio();
        });
        if ($img->save($smallthumbnailpath)) {
            $return = $name;
        }
    } else {
        $return = false;
    }
    return $return;
}

function strRandom()
{
    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return substr(str_shuffle($permitted_chars), 0, 10);
}

function charRandom($limit = null)
{
    $limit = ($limit == null) ? 10 : $limit;
    $permitted_chars = 'abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';
    return substr(str_shuffle($permitted_chars), 0, $limit);
}

/*
     *  Hàm lấy thông tin ra xem có phải là sku auto hay là sku fixed cứng hay không.
     * */
function getInfoSkuName($sku_auto_id)
{
    $array = [];
    // lấy ra tên của sku auto id
    $sku_auto = \DB::table('sku_autos')->select('sku')->where('id',$sku_auto_id)->first();
    if ($sku_auto != NULL)
    {
        $list_woo_template_id = \DB::table('woo_templates')->where('sku_auto_id',$sku_auto_id)->pluck('template_id')->toArray();
        $scrap_count = 0;
        $driver_count = 0;
        if (sizeof($list_woo_template_id) > 0)
        {
            // đếm số lượng product đã sử dụng sku này
            $scrap_count = \DB::table('scrap_products')->whereIn('template_id', $list_woo_template_id)
                ->whereNotNull('sku_auto_string')->count();
            $driver_count = \DB::table('woo_product_drivers')->whereIn('template_id', $list_woo_template_id)
                ->whereNotNull('sku_auto_string')->count();
        }
        $count = 100 + $scrap_count + $driver_count;
        $last_Prefix = strtoupper(charRandom(1));
        $array = [
            'sku' => $sku_auto->sku,
            'count' => $count,
            'last_prefix' => $last_Prefix
        ];
    }
    return $array;
}

/*Hàm check sku string đã tồn tại chưa*/
function getSkuAutoId($string = null)
{
    $sku_auto_id = 0;
    if ($string != '')
    {
        $sku_auto_string = strtoupper('A'.$string);
        $check_exists = \DB::table("sku_autos")->select('id')->where('sku',$sku_auto_string)->first();
        if($check_exists == NULL) {
            $sku_auto_id = \DB::table('sku_autos')->insertGetId([
                'sku' => $sku_auto_string,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ]);
        } else {
            $sku_auto_id = $check_exists->id;
        }
    }
    return $sku_auto_id;
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
function order_status()
{
    $order_status = ['on-hold', 'processing'];
    return $order_status;
}

function showCurrency($monney)
{
    $formatter = new \NumberFormatter('en_US',  \NumberFormatter::CURRENCY);
    return $formatter->formatCurrency($monney, 'USD');
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

function createDirFullInfo($name, $parent_path = null)
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
        $return = $dir;
    }
    return $return;
}

function checkDirExist($name, $path, $parent_path)
{
    $name = trim($name);
    $return = false;
    $recursive = false; // Get subdirectories also?
    if ($path == '') {
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
    if ($path == '') {
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

function upFile_FullInfo($path_info, $path = null, $new_name = null)
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
            $return = $file;
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
    if ($check_before) {
        $return = true;
    }
    return $return;
}

function checkFileExistByBaseName($basename, $parent_path)
{
    $return = false;
    $path = trim($basename);
    $recursive = false; // Get subdirectories also?
    $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
        ->where('type', '=', 'file')
        ->where('path', '=', $path)
        ->first();
    if ($check_before) {
        $return = true;
    }
    return $return;
}

function checkFileExistFullInfo($filename, $parent_path)
{
    $return = false;
    $name = trim($filename);
    $recursive = false; // Get subdirectories also?
    $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
        ->where('type', '=', 'file')
        ->where('name', '=', $filename)
        ->first();
    if ($check_before) {
        $return = $check_before;
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
    $order_new = getNewWorking();
    $order_working = getworkingOrder();
    $order_checking = getCheckingOrder();

    $idea_new = getIdeaNew();
    $idea_check = getIdeaCheck();

    $new_orders = getNewOrder();

    $data['pub'] = [
        'new' => $order_new,
        'working' => $order_working,
        'order_checking' => $order_checking,
        'idea_new' => $idea_new,
        'idea_check' => $idea_check,
        'new_order' => $new_orders
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

function getNewWorking()
{
    $where = [
        ['status', '=', env('STATUS_WORKING_NEW')]
    ];
    return \DB::table('designs')
        ->where($where)
        ->count();
}

function getNewOrder()
{
    $where = [
        ['status', '=', env('STATUS_WORKING_NEW')],
    ];
    return \DB::table('woo_orders')
        ->join('designs','woo_orders.design_id','=', 'designs.id')
        ->where('designs.status', '<>', env('STATUS_SKIP'))
        ->where('woo_orders.status', '=', env('STATUS_WORKING_NEW'))
        ->whereIn('woo_orders.order_status', order_status())
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
    if (!File::exists($path)) {
        umask(0);
        $result = File::makeDirectory($path, 0777, true);
    }
    return $result;
}

/* Tao file json moi */
function writeFileJson($path_file, $data)
{
    // Write File
    $newJsonString = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $result = File::put($path_file, stripslashes($newJsonString), 'public');
    return $result;
}

/* Đọc file json*/
function readFileJson($path_file)
{
    $jsonString = File::get($path_file);
    $data = json_decode($jsonString, true);
    return $data;
}

function readFileExcel($path_file)
{
    $return = false;
    $dt = Excel::load($path_file)->get()->toArray();
    if (sizeof($dt) > 0) {
        $return = $dt;
    }
    return $return;
}

function createFileExcel($name_file, $data, $path, $sheet_name = null)
{
    if ($sheet_name == null) {
        $sheet_name = 'Sheet 1';
    } else {
        $sheet_name = (strlen($sheet_name) > 30) ? 'Sheet 1' : $sheet_name;
    }
    $check = Excel::create($name_file, function ($excel) use ($data, $sheet_name) {
        $excel->sheet($sheet_name, function ($sheet) use ($data) {
            $sheet->fromArray($data);
        });
    })->store('csv', $path, true);
    if ($check) {
        return true;
    } else {
        return false;
    }
}

/*
 *  $ext = ['jpg', 'jpeg', 'png'];
 *  $str_compare = '-PID-';
 * */
function filterFileUploadBefore($files, $str_compare = null, $ext_array = null)
{
    if ($ext_array == null)
    {
        $ext = ['jpg', 'jpeg', 'png'];
    } else {
        $ext = $ext_array;
    }

    if ($str_compare == null)
    {
        $str_compare = '';
    }

    $message = '';
    $paths = array(
        env('DIR_TMP'),
        env('DIR_NEW'),
        env('DIR_WORKING'),
        env('DIR_CHECK'),
        env('DIR_THUMB')
    );
    foreach ($paths as $path) {
        if (!\File::exists(public_path($path))) {
            \File::makeDirectory(public_path($path), $mode = 0777, true, true);
        }
    }
    $filter_files = array();
    foreach ($files as $file) {
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $file->getClientOriginalName();
        if ($file->getSize() <= env('UPLOAD_SIZE_MAX')) {
            if (in_array(strtolower($extension), $ext)) {
                if (strlen($str_compare) > 0 && strpos($filename, $str_compare) === false) {
                    $message .= getErrorMessage('File ' . $filename . ' sai định dạng tên. Mời đổi lại tên.');
                    continue;
                }
                if ($file->move(public_path(env('DIR_TMP')), $filename)) {
                    $filter_files[] = $filename;
                } else {
                    $message .= getErrorMessage('Upload lỗi file :' . $filename . '. Làm ơn thử lại nhé.');
                }
            } else {
                $message .= getErrorMessage('File ' . $filename . ' không đúng định dạng file dưới đây: '.implode(',', $ext_array));
            }
        } else {
            $message .= getErrorMessage('File ' . $filename . ' lớn hơn '.(int)(env('UPLOAD_SIZE_MAX')/1000000).' MB');
        }
    }
    return array('message' => $message, 'files' => $filter_files);
}
