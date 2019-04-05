<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

function logfile($str)
{
    \Log::info($str);
}

function statusJob($status, $redo, $reason)
{
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
    return '<img src="' . $path . '" class="materialboxed img-thumbnail" height="' . $height . '" title="' . $name . '"/>';
}

function thumb_w($path, $width, $name)
{
    return '<img src="' . $path . '" class="materialboxed img-thumbnail" width="' . $width . '" title="' . $name . '"/>';
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
        if (Storage::cloud()->move($check_before['path'], $new_name)) {
            $return = true;
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
