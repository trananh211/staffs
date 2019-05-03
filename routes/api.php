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

Route::get('/test-new-order/{filename}','ApiController@testNewOrder');

Route::post('/update-product/','ApiController@updateProduct');

Route::get('/test-update-product/{filename}','ApiController@testUpdateProduct');

Route::get('/update-sku','ApiController@updateSku');

Route::get('/email-test','ApiController@sendEmail');
