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
        $schedule->exec('chmod -R 777 '.public_path())->dailyAt('00:10');
        $schedule->exec('chmod -R 777 '.storage_path())->dailyAt('00:20');
        /*Export file excel lên thư mục fulfill*/
        $schedule->call('App\Http\Controllers\GoogleController@fulfillment')->dailyAt('00:30');
        /*Upload file image lên thư mục fulfill*/
        $schedule->call('App\Http\Controllers\GoogleController@uploadFileDriver')->everyTenMinutes()
            ->between('1:00', '23:00');
        $schedule->call('App\Http\Controllers\ApiController@checkPaymentAgain')->hourlyAt(17);

        /*Test*/
//        /*
//        $schedule->call('App\Http\Controllers\GoogleController@fulfillment')->everyMinute();
//        $schedule->call('App\Http\Controllers\GoogleController@uploadFileDriver')->everyMinute();
//        $schedule->call('App\Http\Controllers\ApiController@checkPaymentAgain')->everyMinute();
//        */

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
