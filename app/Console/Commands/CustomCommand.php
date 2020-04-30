<?php

namespace App\Console\Commands;

use App\Http\Controllers\GoogleController;
use Illuminate\Console\Command;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\WooController;
use App\Http\Controllers\TrackingController;

class CustomCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:custom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run code by time';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected $array_minute = [59, 6, 4, 1];
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $minute = date('i');
        $array_minute = $this->array_minute;
        for ($i = 0; $i < sizeof($array_minute); $i++)
        {
            $min = 'time';
            $minute_compare = $array_minute[$i];
            if (($minute % $minute_compare) == 0)
            {
                $min = $minute_compare;
                logfile_system('+++++++++++++++++++++++ Hàm đang chạy bởi '.$min." phut \n");
                break;
            }
        }
        $this->runCommand($min);
    }

    private function runCommand($minute)
    {
        switch ($minute) {
            case 1:
                $this->run1Minute();
                break;
            case 4:
                $this->run4Minute();
                break;
            case 6:
                $this->run6Minute();
                break;
//            case 19:
//                $this->run19Minute();
//                break;
            case 59:
                $this->run59Minute();
                break;
            default:
                echo 'khong run duoc vao thoi gian nay '. $minute;
                break;
        }
    }

    private function run1Minute()
    {
        $api_controller = new ApiController(); // make sure to import the controller
        $google_controller = new GoogleController(); // make sure to import the controller
        $check0 = $google_controller->uploadFileWorkingGoogle(); // tải file working lên google driver
//        $check0 = true;
        if ($check0)
        {
            $check1 = $google_controller->getFileFulfill(); // download file fulfill từ driver về local
            if ($check1)
            {
                // upload image from google driver to product
                $check2 = $api_controller->autoUploadImage();
                if ($check2) {
                    // tạo feed check feed đầu tiên
                    $check3 = $api_controller->reCheckProductInfo();
                    if ($check3)
                    {
                        //Cào sản phẩm
                        $this->call('scrap:product');
                    }
                }
            }
        }
    }

    private function run4Minute()
    {
        echo 'run 4 phut';
        $api_controller = new ApiController(); // make sure to import the controller
        $check = $api_controller->autoUploadProduct();
        if ($check) {
            $check1 = $api_controller->changeInfoProduct(); // thay đổi thông tin product theo template
            if ($check1) {
                $check2 = $api_controller->getCategoryChecking();
            }
        }
    }

    private function run6Minute()
    {
        echo 'run 6 phut';
        // delete file fulfill in system
        $tracking_controller = new TrackingController();
        $check = $tracking_controller->deleteFulfillFile();
//        $check = true;
        if ($check)
        {
            // kiểm tra thông tin tracking supplier đưa và cập nhật vào database nếu thay đổi.
            $check = $tracking_controller->getInfoTracking();
        }
    }

    private function run19Minute()
    {
        $this->checkTemplateScrap();
    }

    private function run59Minute()
    {
        // Cào website
        $this->call('scan:website');
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
