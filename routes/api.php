<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/new-order/','ApiController@newOrder');

Route::post('/update-order/','ApiController@updateOrderWoo');

Route::get('/test-new-order/{filename}','ApiController@testNewOrder');

Route::post('/update-product/','ApiController@updateProduct');

Route::get('/test-update-product/{filename}','ApiController@testUpdateProduct');

Route::get('/update-sku','ApiController@updateSku');
Route::get('/update-design-id','ApiController@updateDesignId');

Route::get('/email-test','ApiController@sendEmail');

Route::get('/paypal-test','PaypalController@test');

Route::get('/paypal-id','PaypalController@updatePaypalId');

Route::get('/paypal-update','ApiController@updateOrderPaypal');

Route::get('/check-payment-again','ApiController@checkPaymentAgain');

Route::get('/dir-fulfill/{folder}','ApiController@getStructFolder');
