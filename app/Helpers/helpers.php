<?php

use Carbon\Carbon;

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
