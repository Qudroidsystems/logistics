<?php

namespace App\Console\Commands;

use App\Models\BackupSetting;
use App\Services\Backup\DatabaseBackupService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Runs a scheduled database backup when the configured schedule is due.
 * Called every ~15 min by the scheduler; it decides whether it's time.
 * `--force` runs immediately regardless of schedule.
 */
class RunDatabaseBackup extends Command
{
    protected $signature = 'backup:run {--force : Run now, ignoring the schedule}';
    protected $description = 'Create a scheduled database backup (emailed if configured)';

    public function handle(DatabaseBackupService $svc): int
    {
        if (!Schema::hasTable('backup_settings')) {
            $this->warn('Backup tables not installed.'); return self::SUCCESS;
        }

        $s = BackupSetting::current();
        $force = (bool) $this->option('force');

        if (!$force) {
            if (!$s->enabled) { $this->info('Scheduled backups are off.'); return self::SUCCESS; }
            if (!$this->isDue($s)) { $this->info('Not due yet.'); return self::SUCCESS; }
        }

        $result = $svc->run($force ? 'manual' : 'scheduled', $s->updated_by, email: (bool) $s->email);
        $s->last_run_at = now(); $s->save();

        $this->info($result['message']);
        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }

    protected function isDue(BackupSetting $s): bool
    {
        $now = Carbon::now();
        [$h, $m] = array_pad(explode(':', $s->run_time), 2, 0);
        // within the run window and not already run in the last ~20h
        if ($now->hour !== (int) $h || abs($now->minute - (int) $m) > 12) return false;
        if ($s->last_run_at && $s->last_run_at->diffInHours($now) < 20) return false;

        return match ($s->frequency) {
            'weekly'  => $now->dayOfWeek === (int) $s->day_of_week,
            'monthly' => $now->day === min((int) $s->day_of_month, 28),
            default   => true, // daily
        };
    }
}
