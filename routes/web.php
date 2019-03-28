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

    /*S Admin*/
    Route::get('woo-list-store','WooController@listStore');
    Route::get('woo-webhooks','WooController@webhooks');
    /*End S Admin*/
    Route::get('checking','WooController@checking');
    /**/

    /*QC + Admin*/
    Route::get('send-customer/{order_id}','WooController@sendCustomer');
    Route::post('redo-designer','WooController@redoDesigner');
    Route::get('review-customer','WooController@reviewCustomer');
    Route::post('ajax_done_job/action', 'WooController@eventQcDone')->name('ajaxdonejob.action');
    Route::get('supplier','WooController@supplier');
    Route::get('new-job-idea','WooController@createNewJob');
    Route::post('new-job-idea','WooController@saveNewJob')->name('ajaxnewjob.action');
    Route::get('list-idea','WooController@listIdea');
    Route::get('list-idea-done','WooController@listIdeaDone');
    Route::post('ajax-idea-send-qc','WooController@axIdeaSendQc');
    Route::post('ajax-redo-idea','WooController@axRedoIdea')->name('ajaxredoidea.action');
    Route::post('ajax-upload-idea','WooController@axUploadIdea');

    /*End QC + Admin*/

    /*Staff*/
    Route::get('staff-dashboard','WooController@staffDashboard');
    Route::get('staff-get-job','WooController@staffGetJob');
    Route::get('detail-order/{order_id}','WooController@detailOrder');

    Route::get('staff-done-job/{up_id}','WooController@staffDoneJob');
    Route::post('staff-upload', 'WooController@staffUpload')->name('staff.upload');
    Route::post('ajax_upload/action', 'WooController@action')->name('ajaxupload.action');
    Route::get('new-idea','WooController@doNewIdea');
    Route::post('ideaUpload', 'WooController@uploadIdea')->name('ajaxIdeaUpload.action');
    /*End Staff*/
});

Auth::routes();

//Route::filter('auth', function()
//{
//    if (Auth::guest())
//    {
//        if (Request::ajax())
//        {
//            return Response::make('Unauthorized', 401);
//        }
//        else
//        {
//            return Redirect::guest('your_desired_route');
//        }
//    }
//});
