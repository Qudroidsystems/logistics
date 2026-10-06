<?php

namespace App\Console\Commands;

use App\Services\Accounting\LedgerPoster;
use Illuminate\Console\Command;

class SyncFeesToLedger extends Command
{
    protected $signature = 'accounting:sync-fees {--from=} {--to=}';
    protected $description = 'Post school-fee receipts to the general ledger (one journal per day and channel; never twice).';

    public function handle(LedgerPoster $poster): int
    {
        if (!LedgerPoster::available()) { $this->warn('Ledger tables or chart of accounts not ready.'); return self::SUCCESS; }
        $r = $poster->syncFees($this->option('from'), $this->option('to'));
        $this->info("Posted ₦" . number_format($r['amount'], 2) . " across {$r['days']} day(s).");
        return self::SUCCESS;
    }
}
