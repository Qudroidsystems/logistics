<?php

namespace App\Console\Commands;

use App\Modules\Payments\ETransferService;
use Illuminate\Console\Command;

/** Closes e-Transfer payments still waiting after the hold time set under Payment Gateways, and tells the customers. */
class ExpireETransfers extends Command
{
    protected $signature = 'etransfer:expire';

    protected $description = 'Close e-Transfer payments that never arrived';

    public function handle(ETransferService $etransfers): int
    {
        $this->info($etransfers->expire().' e-Transfer payment(s) closed.');

        return self::SUCCESS;
    }
}
