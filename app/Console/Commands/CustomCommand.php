<?php

namespace App\Console\Commands;

use App\Http\Controllers\GoogleController;
use Illuminate\Console\Command;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\WooController;

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

    protected $array_minute = [59, 30, 6, 4, 1];
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
        $check = true;
        // upload image from google driver to product
        $check = $api_controller->autoUploadImage();
//        $check = $api_controller->getCategoryChecking();
        if ($check) {
            // tạo feed check feed đầu tiên
            $check2 = $api_controller->reCheckProductInfo();
            if ($check2)
            {
                //Cào sản phẩm
                $this->call('scrap:product');
            }
        }
    }

    private function run2Minute()
    {
        echo 'run 2 phut';
    }

    private function run4Minute()
    {
        echo 'run 4 phut';
        $api_controller = new ApiController(); // make sure to import the controller
        $check = $api_controller->autoUploadProduct();
        if ($check){
            $api_controller->getCategoryChecking();
        }
    }

    private function run6Minute()
    {
        echo 'run 6 phut';
        $api_controller = new ApiController();
        // up load product from google driver
        $check = $api_controller->autoUploadProduct();

        if ($check)
        {
            $google_controller = new GoogleController();
            // up load product fullfill from google driver
            $check2 = $google_controller->uploadFileDriver();
        }
    }

    private function run59Minute()
    {
        // Cào website
        $this->call('scan:website');
    }
}
