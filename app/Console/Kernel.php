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
        // $schedule->command('inspire')->hourly();
        $schedule->command('app:store-employee-monthly-loans')->everyMinute();

        $schedule->command('app:store-employee-monthly-lic')->everyMinute();

        $schedule->command('app:freeze-employee-salary')->everyMinute();

        $schedule->command('app:store-employee-monthly-festival-advance')->everyMinute();

        $schedule->command('app:store-attendance')->everyMinute();

        // $schedule->command('app:update-employee-in-users')->everyMinute();

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
