<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tracking;
use App\Paypal;

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

    public function viewFilterTracking(Request $request)
    {
        $track = new Tracking();
        return $track->viewFilterTracking($request);
    }

    public function getFileTrackingNow($status = null, $order_id = null)
    {
        $track = new Tracking();
        return $track->getFileTrackingNow($status, $order_id);
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

    public function actionUpTracking(Request $request)
    {
        $track = new Tracking();
        return $track->actionUpTracking($request);
    }

    //xóa file sau khi up tracking
    public function deleteFulfillFile()
    {
        $track = new Tracking();
        return $track->deleteFulfillFile();
    }

    public function editTrackingNumber(Request $request)
    {
        $track = new Tracking();
        return $track->editTrackingNumber($request);
    }

    // lấy thông tin cần up lên paypal
    public function getInfoTrackingUpPaypal()
    {
        $paypal = new Paypal();
        return $paypal->getInfoTrackingUpPaypal();
    }
}
