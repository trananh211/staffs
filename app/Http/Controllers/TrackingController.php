<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tracking;

class TrackingController extends Controller
{
    public function getFileTracking()
    {
        $track = new Tracking();
        return $track->getFileTracking();
    }

    public function getInfoTracking()
    {
        $track = new Tracking();
        return $track->getInfoTracking();
    }

    public function tracking()
    {
        $track = new Tracking();
        return $track->tracking();
    }

    public function getTrackingNumber()
    {
        $data = array();
        return view('/addon/show_tracking_form',compact('data'));
    }

    public function postTrackingNumber(Request $request)
    {
        $track = new Tracking();
        return $track->postTrackingNumber($request);
    }
}
