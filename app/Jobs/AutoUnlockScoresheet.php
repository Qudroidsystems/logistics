<?php
// app/Jobs/AutoUnlockScoresheet.php

namespace App\Jobs;

use App\Models\Broadsheets;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoUnlockScoresheet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $broadsheetId;

    public function __construct($broadsheetId)
    {
        $this->broadsheetId = $broadsheetId;
    }

    public function handle()
    {
        $broadsheet = Broadsheets::find($this->broadsheetId);

        if (!$broadsheet) {
            Log::warning("Auto-unlock failed: Broadsheet {$this->broadsheetId} not found");
            return;
        }

        if ($broadsheet->is_locked && $broadsheet->scheduled_unlock_at && now()->gte($broadsheet->scheduled_unlock_at)) {
            $broadsheet->unlock();
            $broadsheet->scheduled_unlock_at = null;
            $broadsheet->unlock_scheduled_by = null;
            $broadsheet->save();

            Log::info("Auto-unlocked scoresheet {$this->broadsheetId} at " . now());
        }
    }
}
