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
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Paypal  $paypal
     * @return \Illuminate\Http\Response
     */
    public function show(Paypal $paypal)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Paypal  $paypal
     * @return \Illuminate\Http\Response
     */
    public function edit(Paypal $paypal)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Paypal  $paypal
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Paypal $paypal)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Paypal  $paypal
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
}
