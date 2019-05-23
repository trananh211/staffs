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
}
