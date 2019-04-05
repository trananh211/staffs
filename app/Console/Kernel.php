<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        /*Export file excel lên thư mục fulfill*/
        $schedule->call('App\Http\Controllers\GoogleController@fulfillment')->everyMinute();
        /*Upload file image lên thư mục fulfill*/
        $schedule->call('App\Http\Controllers\GoogleController@uploadFileDriver')
            ->everyMinute();
        $schedule->call('App\Http\Controllers\ApiController@checkPaymentAgain')->everyMinute()
        ->skip( function(){
            return false;
        });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
