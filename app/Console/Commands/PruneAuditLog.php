<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneAuditLog extends Command
{
    protected $signature = 'audit:prune {--days=730 : keep this many days}';
    protected $description = 'Delete data-change audit entries older than the given number of days';

    public function handle(): int
    {
        if (!Schema::hasTable('audit_logs')) return self::SUCCESS;
        $cutoff = now()->subDays(max(30, (int) $this->option('days')));
        $n = 0;
        // In batches, so a first run on a big table does not hold a long lock.
        do {
            $done = DB::table('audit_logs')->whereIn('id', DB::table('audit_logs')->where('created_at', '<', $cutoff)->orderBy('id')->limit(5000)->select('id'))->delete();
            $n += $done;
        } while ($done > 0);
        $this->info("Removed {$n} old data-change entries.");
        return self::SUCCESS;
    }
}
