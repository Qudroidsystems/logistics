<?php

namespace App\Console\Commands;

use App\Modules\Marketplace\CancellationService;
use Illuminate\Console\Command;

class ExpireUnpaidAgreements extends Command
{
    protected $signature = 'agreements:expire-unpaid {--hours=24}';
    protected $description = 'Cancel locked agreements that were never paid';

    public function handle(CancellationService $svc): int
    {
        $this->info('Expired '.$svc->expireUnpaid((int) $this->option('hours')).' unpaid agreement(s).');

        return self::SUCCESS;
    }
}
