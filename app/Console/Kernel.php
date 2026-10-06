<?php
// app/Console/Kernel.php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\CleanupExpiredLocks::class,
         \App\Console\Commands\FixJuniorGrades::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Run every hour to cleanup any missed auto-unlocks
        $schedule->command('locks:cleanup-expired')->hourly();

        // Run daily at midnight for comprehensive cleanup
        $schedule->command('locks:cleanup-expired')->daily();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
