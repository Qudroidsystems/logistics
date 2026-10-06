<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneActivityLog extends Command
{
    protected $signature = 'activity:prune {--days=365 : keep this many days}';
    protected $description = 'Delete activity log entries older than the given number of days';

    public function handle(): int
    {
        if (!Schema::hasTable('activity_logs')) return self::SUCCESS;
        $n = DB::table('activity_logs')->where('created_at', '<', now()->subDays((int) $this->option('days')))->delete();
        $this->info("Removed {$n} old activity entries.");
        return self::SUCCESS;
    }
}
