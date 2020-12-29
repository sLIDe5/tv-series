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
        $schedule->command('tv-series:fix-missing-images')->weekly();
        $schedule->command('tv-series:get-torrents')->everyTenMinutes()->name('Get torrents')->withoutOverlapping();
        $schedule->command('tv-series:download-torrents')->everyTenMinutes()->name('Download torrents')->withoutOverlapping();
        $schedule->command('tv-series:process-torrents')->everyTenMinutes()->name('Process torrents')->withoutOverlapping();
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
