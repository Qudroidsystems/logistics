<?php

namespace App\Console\Commands;

use App\Modules\Payments\Escrow\EscrowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReleaseExpiredEscrow extends Command
{
    protected $signature = 'escrow:auto-release';
    protected $description = 'Release escrow whose confirmation window has passed with no objection';

    public function handle(EscrowService $escrow): int
    {
        $due = DB::table('escrow_holds')
            ->where('status', 'held')
            ->whereNotNull('release_after')
            ->where('release_after', '<=', now())
            ->pluck('agreement_id');

        $released = 0;
        foreach ($due as $agreementId) {
            try {
                // A shopping errand needs its receipts reconciled first; leave those for review.
                if (DB::table('shopping_requests')->where('agreement_id', $agreementId)->whereNull('actual_spend')->exists()) {
                    continue;
                }
                $escrow->release((int) $agreementId);
                DB::table('delivery_confirmations')
                    ->where('agreement_id', $agreementId)->where('status', 'pending')
                    ->update(['status' => 'auto_confirmed', 'responded_at' => now(), 'updated_at' => now()]);
                $released++;
            } catch (Throwable $e) {
                $this->error("Agreement {$agreementId}: {$e->getMessage()}");
            }
        }

        $this->info("Released {$released} escrow hold(s).");

        return self::SUCCESS;
    }
}
