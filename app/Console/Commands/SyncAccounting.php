<?php

namespace App\Console\Commands;

use App\Modules\Accounting\AccountingSync;
use Illuminate\Console\Command;

class SyncAccounting extends Command
{
    protected $signature = 'accounting:sync';

    protected $description = 'Send each linked provider\'s daily wallet journal to QuickBooks or Xero';

    public function handle(AccountingSync $sync): int
    {
        $this->info($sync->runAll().' day journal(s) sent.');

        return self::SUCCESS;
    }
}
