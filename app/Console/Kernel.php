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

        /** Run every minute specified queue if not already started */
        /*if (stripos((string) shell_exec('ps xf | grep \'[q]ueue:work\''), 'artisan queue:work') === false) {
            $schedule->command('queue:work --queue=default --sleep=2 --tries=3 --timeout=5')
                ->everyMinute()->appendOutputTo(storage_path() . '/logs/scheduler.log');
        }*/
        $schedule->command('queue:work --stop-when-empty')->everyFiveMinutes();
        /*Export file excel lên thư mục fulfill*/
        $schedule->call('App\Http\Controllers\GoogleController@fulfillment')->twiceDaily(1,16);
        /*Upload file image lên thư mục fulfill*/
        $schedule->call('App\Http\Controllers\GoogleController@uploadFileDriver')->everyTenMinutes()
            ->between('1:00', '23:00');
        $schedule->call('App\Http\Controllers\ApiController@checkPaymentAgain')->hourlyAt(17);
        /*Tracking API*/
        $schedule->call('App\Http\Controllers\TrackingController@getFileTracking')->twiceDaily(2,17);
        $schedule->call('App\Http\Controllers\TrackingController@getInfoTracking')->hourlyAt(33);

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
