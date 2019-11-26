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

        /** Quet carrires moi o paypal*/
        $schedule->command('scan:paypal')->dailyAt('23:37');

        /** Run every minute specified queue if not already started */
        $schedule->command('queue:work --stop-when-empty')->everyMinute();
        /*Export file excel lên thư mục fulfill*/
        $schedule->call('App\Http\Controllers\GoogleController@fulfillment')->twiceDaily(1,5);
        $schedule->call('App\Http\Controllers\GoogleController@fulfillment')->twiceDaily(2,6);
        /*Upload file image lên thư mục fulfill*/
        $schedule->call('App\Http\Controllers\GoogleController@uploadFileDriver')->everyFiveMinutes()
            ->between('1:00', '23:00');
//        $schedule->call('App\Http\Controllers\ApiController@checkPaymentAgain')->hourlyAt(17);
        /*Tracking API*/
//        $schedule->call('App\Http\Controllers\TrackingController@getFileTracking')->hourlyAt(13);
//        $schedule->call('App\Http\Controllers\TrackingController@getInfoTracking')->hourlyAt(33);

        /*Upload Product*/
        $schedule->call('App\Http\Controllers\ApiController@autoUploadProduct')->everyFiveMinutes();
        $schedule->call('App\Http\Controllers\ApiController@autoUploadImage')->everyMinute();

        /** Cao san pham*/
        $schedule->command('scan:website')->dailyAt('22:37');
        $schedule->command('scrap:product')->everyFiveMinutes()->between('0:27', '23:37');
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
