<?php

namespace App\Console\Commands;

use App\Http\Controllers\GoogleController;
use Illuminate\Console\Command;
use App\Http\Controllers\ApiController;

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

    protected $array_minute = [7, 4, 3, 1];
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
            case 2:
                $this->run2Minute();
                break;
            case 3:
                $this->run3Minute();
                break;
            case 4:
                $this->run4Minute();
                break;
            case 5:
                $this->run5Minute();
                break;
            case 6:
                $this->run6Minute();
                break;
            case 7:
                $this->run7Minute();
                break;
            case 8:
                $this->run8Minute();
                break;
            case 9:
                $this->run9Minute();
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
//        $check = $api_controller->getCategoryChecking();
        if ($check) {
            // tạo feed check feed đầu tiên
            $check2 = $api_controller->reCheckProductInfo();
            if ($check2)
            {
                // upload sản phẩm lên web từ google driver
                $check3 = $api_controller->autoUploadImage();
            }
        }
    }

    private function run2Minute()
    {
        echo 'run 2 phut';
    }
    private function run3Minute()
    {
        echo 'run 3 phut';
    }

    private function run4Minute()
    {
        echo 'run 4 phut';
        $api_controller = new ApiController(); // make sure to import the controller
        $api_controller->getCategoryChecking();
    }
    private function run5Minute()
    {
        echo 'run 5 phut';

    }

    private function run6Minute()
    {
        echo 'run 6 phut';
        $google_controller = new GoogleController();
        $api_controller = new ApiController();
        $check = $google_controller->uploadFileDriver();
        if ($check)
        {
            $api_controller->autoUploadProduct();
        }
    }

    private function run7Minute()
    {
        echo 'run 7 phut';
    }

    private function run8Minute()
    {
        echo 'run 8 phut';
    }

    private function run9Minute()
    {
        echo 'run 9 phut';
    }
}
