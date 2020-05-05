<?php

namespace App\Console\Commands;

use App\Http\Controllers\TrackingController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\WooController;
use App\Http\Controllers\GoogleController;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "<pre>\n";
//        $controller = new ApiController(); // make sure to import the controller
//        $controller->autoUploadProduct();
//        $controller->autoUploadImage();

        $api_controller = new ApiController();
        $google_controller = new GoogleController();
        $tracking_controller = new TrackingController();
//        $this->checkTemplateScrap();
//        $check0 = $google_controller->uploadFileWorkingGoogle();
//        if($check0)
//        {
//            $check2= $google_controller->getFileFulfill();
//            var_dump($check2);
//        }
//        $check = $api_controller->fixedSkuWrong();
//        $check = $api_controller->getAllOrderOld();
//        $check = $api_controller->changeNameProduct();
//        $check = $api_controller->changeSkuWooOrder();
//        $check = $api_controller->imgThumbProduct();
//        $check = $api_controller->checkPaymentAgain();
//        $check = $tracking_controller->getInfoTracking();
//        $check = $tracking_controller->deleteFulfillFile();

        $check = $google_controller->reDownloadFileFulfill();
//        $check = $google_controller->getFileFulfill();
//        var_dump($check);

//        $this->call('scan:website');
//        $this->call('scrap:product');
    }

    private function checkTemplateScrap()
    {
        logfile_system('=== [Cập nhật các store scrap có sản phẩm mới hay không] ===============================');
        $result = \DB::table('woo_templates')->where('website_id',19)->update([
            'status' => 0,
            'updated_at' => date("Y-m-d H:i:s")
        ]);
        if($result)
        {
            // Cào website
            $this->call('scan:website');
        } else {
            logfile_system('-- [Error] Xảy ra lỗi không thể cập nhật lại template về new');
        }
    }
}
