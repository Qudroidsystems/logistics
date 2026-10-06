<?php
// app/Console/Commands/CleanupExpiredLocks.php

namespace App\Console\Commands;

use App\Models\Broadsheets;
use App\Models\ScoresheetLock;
use Illuminate\Console\Command;

class CleanupExpiredLocks extends Command
{
    protected $signature = 'locks:cleanup-expired';
    protected $description = 'Clean up expired locks that weren\'t auto-unlocked';

    public function handle()
    {
        // Unlock expired individual locks
        $expiredIndividual = Broadsheets::where('is_locked', true)
            ->where('scheduled_unlock_at', '<=', now())
            ->update([
                'is_locked' => false,
                'locked_by' => null,
                'locked_at' => null,
                'lock_reason' => null,
                'scheduled_unlock_at' => null,
                'unlock_scheduled_by' => null,
            ]);

        // Deactivate expired global locks
        $expiredGlobal = ScoresheetLock::where('is_active', true)
            ->where('scheduled_unlock_at', '<=', now())
            ->update(['is_active' => false]);

        $this->info("Cleaned up {$expiredIndividual} expired individual locks and {$expiredGlobal} expired global locks");
    }
}
