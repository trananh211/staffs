<?php

namespace App\Http\Controllers;

use App\Paypal;
use Illuminate\Http\Request;
use App\Goutte\Client;

class PaypalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = array();
        $stores = \DB::table('woo_infos')->select('id', 'name')->get()->toArray();
        $paypals = \DB::table('paypals')
            ->leftjoin('woo_infos', 'paypals.store_id', '=', 'woo_infos.id')
            ->select(
                'paypals.id as paypal_id', 'paypals.email as paypal_email', 'paypals.status', 'paypals.note',
                'woo_infos.name as store_name'
            )
            ->get()->toArray();
        return view('/addon/paypal_connect', compact('data', 'stores', 'paypals'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $paypal = new Paypal();
        return $paypal->create($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Paypal $paypal
     * @return \Illuminate\Http\Response
     */
    public function show(Paypal $paypal)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Paypal $paypal
     * @return \Illuminate\Http\Response
     */
    public function edit(Paypal $paypal)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Paypal $paypal
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Paypal $paypal)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Paypal $paypal
     * @return \Illuminate\Http\Response
     */
    public function destroy(Paypal $paypal)
    {
        //
    }

    public function test()
    {
        $paypal = new Paypal();
        return $paypal->test();
    }

    public function updatePaypalId()
    {
        $paypal = new Paypal();
        return $paypal->updatePaypalId();
    }

    public function edit17TrackCarrier(Request $request)
    {
        $paypal = new Paypal();
        return $paypal->edit17TrackCarrier($request);
    }

    public function carrierSelect()
    {
        $data = array();
        $carriers = \DB::table('17track_carriers')->select('id','name','paypal_carrier_id')->get()->toArray();
        $paypal_carriers = \DB::table('paypal_carriers')->orderBy('name','asc')->pluck('name','id')->toArray();
        return view('/addon/paypal_carriers', compact('data', 'carriers', 'paypal_carriers'));
    }

}
