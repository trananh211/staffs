<?php

namespace App\Console\Commands;

use App\Http\Controllers\TrackingController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\WooController;
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
//        $controller = new ApiController(); // make sure to import the controller
//        $controller->autoUploadProduct();
//        $controller->autoUploadImage();

        $api_controller = new ApiController();
//        $woo_controller = new WooController();
        // up load product from google driver
        $check = $api_controller->getDesignNew();
    }
}
