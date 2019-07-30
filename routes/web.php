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

    /*QC + Admin*/
    Route::get('edit-store/{id_store}','WooController@editStore');
    Route::get('checking','WooController@checking');
    Route::get('working','WooController@working');
    Route::get('fulfillment','GoogleController@fulFillByHand');
    Route::get('see-log','ApiController@seeLog');
    Route::get('detail-log/{logfile}','ApiController@detailLog');
    Route::post('ajax-take-job','WooController@axTakeJob');
    Route::get('send-customer/{order_id}','WooController@sendCustomer');
    Route::post('redo-designer','WooController@redoDesigner');
    Route::get('review-customer','WooController@reviewCustomer');
    Route::post('ajax_done_job/action', 'WooController@eventQcDone')->name('ajaxdonejob.action');
    Route::get('supplier','WooController@supplier');
    Route::get('tracking','TrackingController@tracking');
    Route::get('new-job-idea','WooController@createNewJob');
    Route::post('new-job-idea','WooController@saveNewJob')->name('ajaxnewjob.action');
    Route::get('list-idea','WooController@listIdea');
    Route::get('list-idea-done','WooController@listIdeaDone');
    Route::post('ajax-idea-send-qc','WooController@axIdeaSendQc');
    Route::post('ajax-redo-idea','WooController@axRedoIdea')->name('ajaxredoidea.action');
    Route::post('ajax-upload-idea','WooController@axUploadIdea');
    Route::post('ajax-delete-log','WooController@axDeleteLog');
    Route::get('list-order','WooController@listAllOrder');
    Route::get('list-product','WooController@listAllProduct');
    Route::post('update-order','WooController@updateOrder');
    Route::post('up-design-normal','WooController@upDesignNormal');
    Route::post('ajax-skip-product','WooController@axSkipProduct');

    //woo products create automatic
    Route::get('woo-create-template','WooController@viewCreateTemplate');
    Route::post('woo-check-template','ApiController@checkTemplate');
    Route::post('woo-check-driver-product','WooController@checkDriverProduct');
    Route::post('woo-save-create-template','WooController@saveCreateTemplate');
    Route::get('woo-processing-product','WooController@processingProduct');
    Route::get('test-upload','ApiController@autoUploadProduct');
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

Route::get('test','GoogleController@test');
Route::get('fulfillment','GoogleController@fulfillment');
Route::get('uploadFileDriver','GoogleController@uploadFileDriver');
Route::get('getFileTracking','TrackingController@getFileTracking');
Route::get('getInfoTracking','TrackingController@getInfoTracking');
Route::get('autoGenThumb','WooController@autoGenThumb');

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
