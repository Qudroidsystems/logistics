<?php

namespace App\Console\Commands;

use App\Modules\Recurring\RecurringDeliveryService;
use Illuminate\Console\Command;

/** Books the recurring deliveries coming up within the next day and pays them from the wallet. */
class RunRecurringDeliveries extends Command
{
    protected $signature = 'recurring:run';

    protected $description = 'Book upcoming recurring deliveries';

    public function handle(RecurringDeliveryService $recurring): int
    {
        $this->info($recurring->runDue().' trip(s) handled.');

        return self::SUCCESS;
    }
}
