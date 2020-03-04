<?php

namespace App\Http\Controllers;

use App\Api;
use Illuminate\Http\Request;
use Auth;
use DB;
use App\User; // this to add User Model
use App\WooInfo;
use App\Working;
use Validator;

class WooController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /*S Admin*/
    public function connect()
    {
        $data = infoShop();
        return view('admin/woo/connect', compact('data'));
    }

    public function doConnect(Request $request)
    {
        $data = $request->all();
        $str = '';
        if (array_key_exists('id_store', $data)) {
            $woo_info = WooInfo::find($request->id_store);
            $str = "Cập nhật thông tin thành công";
        } else {
            $woo_info = new WooInfo();
            $str = "Tạo mới thông tin store thành công";
        }
        $woo_info->name = $request->name;
        $woo_info->url = $request->url;
        $woo_info->email = $request->email;
        $woo_info->password = $request->password;
        $woo_info->host = $request->host;
        $woo_info->port = $request->port;
        $woo_info->security = $request->security;
        $woo_info->consumer_key = $request->consumer_key;
        $woo_info->consumer_secret = $request->consumer_secret;
        $woo_info->sku = $request->sku;
        $woo_info->status = 0;
        $result = $woo_info->save();
        if ($result) {
            $status = 'success';
            $message = $str;
        } else {
            $status = 'error';
            $message = 'Lưu thông tin thất bại';
        }
        return back()->with($status, $message);
    }

    public function listStore()
    {
        $stores = WooInfo::get();
        $data = infoShop();
        return view('admin/woo/list_store', compact('stores', 'data'));
    }

    public function editStore($id_store)
    {
        $data = infoShop();
        $store = \DB::table('woo_infos')->where('id', $id_store)->get();
        return view('admin/edit_store', compact('data'));
    }

    public function webhooks()
    {
        $data = infoShop();
        return view('admin/woo/webhooks', compact('data'));
    }
    /*End S Admin*/

    /*Staff */
    public function workingDashboard()
    {
        $work = new Working();
        $lists = $work->listOrder();
        $data = infoShop();
        return view('staff/staff', compact('data', 'lists'));
    }

    public function staffGetJob()
    {
        $work = new Working();
        return $work->staffGetJob();
    }

    public function staffDoneJob($up_id)
    {
        return view('staff/staff_done', ['up_id' => $up_id]);
    }

    public function staffUpload()
    {
        $work = new Working();
        return $work->staffUpload();
    }

    public function action(Request $request)
    {
        $work = new Working();
        return $work->staffUpload($request);
    }

    public function uploadIdea(Request $request)
    {
        $work = new Working();
        return $work->uploadIdea($request);
    }

    public function doNewIdea()
    {
        $work = new Working();
        return $work->doNewIdea();
    }
    /*End Staff*/

    /*Admin + QC*/
    public function checking()
    {
        $work = new Working();
        return $work->checking();
    }

    public function working()
    {
        $work = new Working();
        return $work->working();
    }

    public function sendCustomer($order_id)
    {
        $work = new Working();
        return $work->sendCustomer($order_id);
    }

    public function redoDesigner(Request $request)
    {
        $work = new Working();
        return $work->redoDesigner($request);
    }

    public function redoingJobStaff($working_id)
    {
        $work = new Working();
        return $work->redoingJobStaff($working_id);
    }

    public function reviewCustomer()
    {
        $work = new Working();
        return $work->reviewCustomer();
    }

    public function eventQcDone(Request $request)
    {
        $work = new Working();
        return $work->eventQcDone($request);
    }

    public function supplier()
    {
        $work = new Working();
        $lists = $work->supplier();
        $data = infoShop();
        return view('/admin/supplier', compact('lists', 'data'));
    }

    public function createNewJob()
    {
        $work = new Working();
        $users = $work->listWorker();
        $data = infoShop();
        return view('/admin/newjob')->with(compact('users', 'data'));
    }

    public function saveNewJob(Request $request)
    {
        $work = new Working();
        return $work->saveNewJob($request);
    }

    public function listIdea()
    {
        $work = new Working();
        return $work->listIdea();
    }

    public function listIdeaDone()
    {
        $work = new Working();
        return $work->listIdeaDone();
    }

    public function axIdeaSendQc(Request $request)
    {
        $work = new Working();
        return $work->axIdeaSendQc($request);
    }

    public function axRedoIdea(Request $request)
    {
        $work = new Working();
        return $work->axRedoIdea($request);
    }

    public function axUploadIdea(Request $request)
    {
        $work = new Working();
        return $work->axUploadIdea($request);
    }

    public function axTakeJob(Request $request)
    {
        $work = new Working();
        return $work->axTakeJob($request);
    }

    public function axDeleteLog(Request $request)
    {
        $work = new Working();
        return $work->axDeleteLog($request);
    }

    public function listAllOrder()
    {
        $work = new Working();
        return $work->listAllOrder();
    }

    public function listAllProduct()
    {
        $work = new Working();
        return $work->listAllProduct();
    }

    public function updateOrder(Request $request)
    {
        $api = new Api();
        return $api->updateOrder($request);
    }

    public function upDesignNormal(Request $request)
    {
//        $work = new Working();
//        return $work->upDesignNormal($request);
    }

    public function axSkipProduct(Request $request)
    {
        $work = new Working();
        return $work->axSkipProduct($request);
    }

    public function axReSendEmail(Request $request)
    {
        $work = new Working();
        return $work->axReSendEmail($request);
    }

    public function autoGenThumb()
    {
        $work = new Working();
        return $work->autoGenThumb();
    }

    /*Tạo sản phẩm */
    public function viewCreateTemplate()
    {
        $work = new Working();
        return $work->viewCreateTemplate();
    }

    public function checkDriverProduct(Request $request)
    {
        $work = new Working();
        return $work->checkDriverProduct($request);
    }

    public function saveCreateTemplate(Request $request)
    {
        $work = new Working();
        return $work->saveCreateTemplate($request);
    }

    public function processingProduct()
    {
        $work = new Working();
        return $work->processingProduct();
    }

    public function getSupplier()
    {
        $data = array();
        $lists = \DB::table('suppliers')
            ->select('id', 'name', 'status', 'note')
            ->orderBy('status', 'DESC')
            ->get()->toArray();
        return view('/admin/woo/supplier')->with(compact('lists', 'data'));
    }

    public function getNewSupplier()
    {
        $data = array();
        return view('/admin/woo/add_new_supplier')->with(compact('data'));
    }

    public function addNewSupplier(Request $request)
    {
        $work = new Working();
        return $work->addNewSupplier($request);
    }

    public function deleteSupplier($supplier_id)
    {
        $work = new Working();
        return $work->deleteSupplier($supplier_id);
    }

    public function editSupplier($supplier_id)
    {
        $work = new Working();
        return $work->editSupplier($supplier_id);
    }

    public function viewFromCreateTemplate()
    {
        $work = new Working();
        return $work->viewFromCreateTemplate();
    }

    public function deleteAllTemplate($template_id)
    {
        $work = new Working();
        return $work->deleteAllTemplate($template_id);
    }

    public function deleteAllProductTemplate($woo_template_id, $type)
    {
        $work = new Working();
        return $work->deleteAllProductTemplate($woo_template_id, $type);
    }

    public function deletedCategories()
    {
        $work = new Working();
        return $work->deletedCategories();
    }

    public function actionDeletedCategories(Request $request)
    {
        $work = new Working();
        return $work->actionDeletedCategories($request);
    }

    public function getListTemplate()
    {
        $lists = \DB::table('woo_templates as w_temp')
            ->leftjoin('woo_infos', 'w_temp.store_id', '=', 'woo_infos.id')
            ->leftjoin('suppliers as sup', 'w_temp.supplier_id', '=', 'sup.id')
            ->select(
                'w_temp.id', 'w_temp.product_name', 'w_temp.supplier_id', 'w_temp.store_id', 'w_temp.template_id',
                'w_temp.base_price', 'w_temp.variation_change_id', 'w_temp.website_id', 'w_temp.status',
                'woo_infos.name as store_name',
                'sup.name as sup_name'
            )
            ->orderBy('w_temp.store_id')
            ->get()->toArray();
        $suppliers = \DB::table('suppliers')->select('id', 'name')->get()->toArray();
        $variation_changes = \DB::table('variation_changes')->select('id','name')->get()->toArray();
        $data = array();
        return view('/admin/woo/list_templates')
            ->with(compact('lists', 'suppliers','variation_changes', 'data'));
    }

    public function editWooTemplate(Request $request)
    {
        $work = new Working();
        return $work->editWooTemplate($request);
    }

    public function scanAgainTemplate($woo_template_id)
    {
        $work = new Working();
        return $work->scanAgainTemplate($woo_template_id);
    }

    public function getListConvertVariation()
    {
        $data = array();
        $lists = \DB::table('variation_changes as var_chg')
            ->leftjoin('suppliers', 'var_chg.suplier_id','=', 'suppliers.id')
            ->select('var_chg.id','var_chg.name as variation_name','suppliers.name as supplier_name')
            ->orderBy('var_chg.id','DESC')
            ->get()->toArray();
        return view('/admin/woo/list_convert_variation',compact('data','lists'));
    }

    public function deleteConvertVariation($id)
    {
        $work = new Working();
        return $work->deleteConvertVariation($id);
    }

    public function getConvertVariation()
    {
        $data = array();
        $supliers = \DB::table('suppliers')->select('id','name')->get()->toArray();
        return view('/admin/woo/get_convert_variation',compact('data','supliers'));
    }

    public function ajaxPutConvertVariation(Request $request)
    {
        $work = new Working();
        return $work->ajaxPutConvertVariation($request);
    }

    public function ajaxCheckVariationExist(Request $request)
    {
        $work = new Working();
        return $work->ajaxCheckVariationExist($request);
    }
    /*End Tạo sản phẩm */

    /*lay link de get keyword*/
    public function listCategories()
    {
        $work = new Working();
        return $work->listCategories();
    }

    public function deleteWooCategory($woo_category_id)
    {
        $work = new Working();
        return $work->deleteWooCategory($woo_category_id);
    }

    public function editKeywordCategory($woo_category_id)
    {
        $work = new Working();
        return $work->editKeywordCategory($woo_category_id);
    }

    public function showKeywordCategory(Request $request)
    {
        $work = new Working();
        return $work->showKeywordCategory($request);
    }

    public function addListKeyword(Request $request)
    {
        $work = new Working();
        return $work->addListKeyword($request);
    }

    public function processFeedStore(Request $request)
    {
        $work = new Working();
        return $work->processFeedStore($request);
    }

    public function getStoreFeed()
    {
        $work = new Working();
        return $work->getStoreFeed();
    }

    // xóa file google feed
    public function feedDeleteFile($google_feed_id)
    {
        $work = new Working();
        return $work->feedDeleteFile($google_feed_id);
    }

    //download file google feed
    public function feedGetFile($google_feed_id)
    {
        $work = new Working();
        return $work->feedGetFile($google_feed_id);
    }

    //show danh sach cac design chua duoc thiet ke
    public function listDesignNew()
    {
        $work = new Working();
        return $work->listDesignNew();
    }

    public function getDesignNew()
    {
        $work = new Working();
        return $work->getDesignNew();
    }
    /*End Admin + QC*/
}
