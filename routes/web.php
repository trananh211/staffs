<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::get('/home', 'UserController@index')->name('home');

Auth::routes();

// Sửa đường dẫn trang chủ mặc định
Route::get('/', 'UserController@index');
Route::get('/home', 'UserController@index');

// Đăng ký thành viên
Route::get('register', 'Auth\RegisterController@getRegister');
Route::post('register', 'Auth\RegisterController@postRegister');

/*// Đăng nhập và xử lý đăng nhập
Route::get('login', [ 'as' => 'login', 'uses' => 'Auth\LoginController@getLogin']);
Route::post('login', [ 'as' => 'login', 'uses' => 'Auth\LoginController@postLogin']);

// Đăng xuất
Route::get('logout', [ 'as' => 'logout', 'uses' => 'Auth\LogoutController@getLogout']);*/

Route::middleware('auth')->group(function () {
    Route::get('woo_connect','WooController@connect');
    Route::post('woo_connect','WooController@doConnect');

    Route::get('woo-list-store','WooController@listStore');
    Route::get('woo-webhooks','WooController@webhooks');
});

//Route::middleware('web')->group(function () {
//    /*Woocommerce Webhook*/
//    Route::get('update-order','WooController@updateOrder');
//});
Route::get('/test','WooController@updateOrder');
Auth::routes();



