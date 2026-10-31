<?php

namespace App\Console\Commands;

use App\Modules\System\SystemHealth;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

/** Checks that the scheduled commands ran on time and the queue is moving; alerts staff and updates Ops > System. */
class WatchSystem extends Command
{
    protected $signature = 'system:watch';

    protected $description = 'Check background work and alert staff about problems';

    public function handle(SystemHealth $health, Schedule $schedule): int
    {
        $problems = $health->check($schedule);
        foreach ($problems as $p) {
            $this->warn($p);
        }
        $this->info($problems ? count($problems).' problem(s).' : 'All good.');

        return self::SUCCESS;
    }
}
