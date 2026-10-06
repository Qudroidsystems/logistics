<?php

namespace App\Console\Commands;

use App\Services\Calendar\CalendarFeeSyncService;
use Illuminate\Console\Command;

class SyncCalendarFees extends Command
{
    protected $signature = 'calendar:sync-fees';
    protected $description = 'Sync fee/bill due-dates into calendar events (idempotent)';

    public function handle(CalendarFeeSyncService $svc): int
    {
        $r = $svc->sync();
        $this->info("Fee deadlines — created: {$r['created']}, updated: {$r['updated']}, removed: {$r['removed']}.");
        return self::SUCCESS;
    }
}
