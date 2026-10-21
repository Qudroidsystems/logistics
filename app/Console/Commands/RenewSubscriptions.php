<?php

namespace App\Console\Commands;

use App\Modules\Subscriptions\SubscriptionService;
use Illuminate\Console\Command;

/** Starts the next month of business plans whose month has ended: takes the fee from the wallet, or marks the plan past due. */
class RenewSubscriptions extends Command
{
    protected $signature = 'subscriptions:renew';

    protected $description = 'Renew business plans whose month has ended';

    public function handle(SubscriptionService $subscriptions): int
    {
        $this->info($subscriptions->renewDue().' plan(s) checked.');

        return self::SUCCESS;
    }
}
