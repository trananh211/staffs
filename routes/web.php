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
    Route::get('checking','WooController@getChecking');
    Route::get('working','WooController@checkWorking');
    Route::get('fulfillment','GoogleController@fulFillByHand');
    Route::get('see-log','ApiController@seeLog');
    Route::get('detail-log/{logfile}','ApiController@detailLog');
    Route::post('ajax-take-job','WooController@axTakeJob');
    Route::post('ajax-give-job-staff','WooController@axGiveJobStaff');
    Route::get('send-customer/{order_id}','WooController@sendCustomer');
    Route::post('redo-designer','WooController@redoDesigner');
    Route::get('review-customer','WooController@reviewCustomer');
    Route::get('list-job-done','WooController@listJobDone');
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
    Route::post('ajax-re-send-email','WooController@axReSendEmail');
    Route::post('action-deleted-categories','WooController@actionDeletedCategories');
    Route::get('deleted-categories','WooController@deletedCategories');
    Route::get('fulfill-category','WooController@fulfillCategory');
    Route::get('update-variation','WooController@updateVariation');
    Route::get('list-variation-category','WooController@listVariationCategory');
    Route::post('edit-variations','WooController@editVariations');
    Route::post('ajax-choose-variations','WooController@axChooseVariations');
    Route::post('add-list-variation','WooController@addListVariation');
    Route::post('edit-info-fulfills','WooController@editInfoFulfills');
    Route::post('add-new-tool-category','WooController@addNewToolCategory');
    Route::post('edit-tool-category','WooController@editToolCategory');
    Route::get('delete-tool-category/{id}','WooController@deleteToolCategory');
    Route::get('make-template-category/{id}','WooController@makeTemplateCategory');
    Route::get('list-template-category','WooController@listTemplateCategory');
    Route::post('new-template-category','WooController@NewTemplateCategory');
    Route::get('action-fulfill-now','WooController@actionFulfillNow');
    Route::get('fulfill-get-file/{id}','WooController@fulfilGetFile');
    Route::get('fulfill-rescan-file/{id}','WooController@fulfillRescanFile');

    /** Paypal */
    Route::get('paypal-connect','PaypalController@index');
    Route::post('paypal-create','PaypalController@create');

    //woo products create automatic
    Route::get('woo-create-template','WooController@viewCreateTemplate');
    Route::post('woo-check-template','ApiController@checkTemplate');
    Route::post('woo-check-driver-product','WooController@checkDriverProduct');
    Route::post('woo-save-create-template','WooController@saveCreateTemplate');
    Route::get('woo-processing-product','WooController@processingProduct');
    Route::get('test-upload','ApiController@autoUploadProduct');
    Route::get('test-image','ApiController@autoUploadImage');
    Route::get('woo-supplier','WooController@getSupplier');
    Route::get('woo-supplier','WooController@getSupplier');
    Route::get('woo-get-new-supplier','WooController@getNewSupplier');
    Route::post('woo-add-new-supplier','WooController@addNewSupplier');
    Route::get('woo-delete-supplier/{supplier_id}','WooController@deleteSupplier');
    Route::get('woo-get-template','WooController@getListTemplate');
    Route::post('woo-update-template','ApiController@editWooTemplate');
    Route::get('woo-scan-template/{woo_template_id}','WooController@scanAgainTemplate');
    Route::get('woo-list-convert-variation','WooController@getListConvertVariation');
    Route::get('woo-convert-variation','WooController@getConvertVariation');
    Route::get('woo-delete-convert-variation/{id}','WooController@deleteConvertVariation');
    Route::post('js-woo-convert-variation', 'WooController@ajaxPutConvertVariation');
    Route::post('js-check-variation-exist', 'WooController@ajaxCheckVariationExist');
    Route::get('scrap-create-template','WooController@viewFromCreateTemplate');
    Route::post('scrap-save-template','ApiController@scrapSaveTemplate');
    Route::get('woo-deleted-all-template/{woo_template_id}','WooController@deleteAllTemplate');
    Route::get('woo-deleted-all-product/{woo_template_id}&{type}','WooController@deleteAllProductTemplate');
    Route::post('ajax-redo-new-sku','WooController@axRedoNewSKU');
    Route::post('dashboard-date','WooController@dashboardDate');
    Route::post('update-tool-category','WooController@updateToolCategory');
    Route::post('working-change-variation','WooController@workingChangeVariation');

    //lấy link về để cào lấy keyword
    Route::get('list-categories','WooController@listCategories');
    Route::get('keyword-category-edit/{id}','WooController@editKeywordCategory');
    Route::post('ajax-get-all-keyword-category','WooController@showKeywordCategory');
    Route::post('add-list-keyword','WooController@addListKeyword');
    Route::get('get-store','WooController@getStoreFeed');
    Route::post('process-feed-store','WooController@processFeedStore');
    Route::get('feed-delete-file/{google_feed_id}','WooController@feedDeleteFile');
    Route::get('feed-get-file/{google_feed_id}','WooController@feedGetFile');
    Route::get('delete-woo-category/{woo_category_id}','WooController@deleteWooCategory');
    Route::post('get-more-category','ApiController@getMoreWooCategory');
    /*End QC + Admin*/

    /*Staff*/
    Route::get('staff-dashboard','WooController@workingDashboard');
    Route::get('staff-get-job','WooController@staffGetJob');
    Route::get('detail-order/{order_id}','WooController@detailOrder');

    Route::get('staff-done-job/{up_id}','WooController@staffDoneJob');
    Route::post('staff-upload', 'WooController@staffUpload')->name('staff.upload');
    Route::post('ajax_upload/action', 'WooController@staffAction')->name('ajaxupload.action');
    Route::get('new-idea','WooController@doNewIdea');
    Route::post('ideaUpload', 'WooController@uploadIdea')->name('ajaxIdeaUpload.action');
    Route::get('redoing-job/{working_id}','WooController@redoingJobStaff');
    Route::get('staff-skip-job/{working_id}','WooController@staffSkipJob');
    /*End Staff*/
});

Route::get('test','GoogleController@test');
Route::get('fulfillment','GoogleController@fulfillment');
Route::get('upload-file-driver','GoogleController@uploadFileDriver');
Route::get('upload-file-driver-auto','GoogleController@uploadProductAutoToDriver');
Route::get('getFileTracking','TrackingController@getFileTracking');
Route::get('getInfoTracking','TrackingController@getInfoTracking');
Route::get('autoGenThumb','WooController@autoGenThumb');
Route::get('tracking-number','TrackingController@getTrackingNumber');
Route::post('pay-tracking','TrackingController@postTrackingNumber');

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
