<?php
// app/Jobs/AutoUnlockGlobalLock.php

namespace App\Jobs;

use App\Models\ScoresheetLock;
use App\Models\Broadsheets;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoUnlockGlobalLock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $lockId;

    public function __construct($lockId)
    {
        $this->lockId = $lockId;
    }

    public function handle()
    {
        $lock = ScoresheetLock::find($this->lockId);

        if (!$lock || !$lock->is_active) {
            return;
        }

        if ($lock->scheduled_unlock_at && now()->gte($lock->scheduled_unlock_at)) {
            // Deactivate global lock
            $lock->is_active = false;
            $lock->save();

            // Unlock all associated broadsheets
            $count = Broadsheets::where([
                'subjectclass_id' => $lock->subjectclass_id,
                'term_id' => $lock->term_id,
                'is_locked' => true,
            ])
            ->whereHas('broadsheetRecord', function($q) use ($lock) {
                $q->where('session_id', $lock->session_id);
            })
            ->update([
                'is_locked' => false,
                'locked_by' => null,
                'locked_at' => null,
                'lock_reason' => null,
                'scheduled_unlock_at' => null,
            ]);

            Log::info("Auto-unlocked global lock {$this->lockId}, unlocked {$count} scoresheets at " . now());
        }
    }
}
