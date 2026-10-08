<?php

namespace App\Console\Commands;

use App\Modules\Payments\PaymentReconciler;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Re-check unconfirmed online payments with their gateway (covers webhooks that never arrived)';

    public function handle(PaymentReconciler $reconciler): int
    {
        $this->info('Queued '.$reconciler->sweep().' payment checks.');

        return self::SUCCESS;
    }
}
