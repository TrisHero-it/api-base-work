<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:load-youtube-command')->dailyAt('00:00');
        $schedule->command('app:add_day_off_to_accounts_every_month')->lastDayOfMonth();
        $schedule->command('app:check-duration-resource')->everyMinute();
        $schedule->command('app:reset-work-form-home-of-account')->dailyAt('00:01');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
