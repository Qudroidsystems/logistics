<?php

namespace App\Modules\Dispatch\Jobs;

use App\Modules\Dispatch\DispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Runs a moment after an offer's 30 seconds are up; does nothing if the driver already answered. */
class ExpireDispatchOffer implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $offerId)
    {
    }

    public function handle(DispatchService $dispatch): void
    {
        $dispatch->expire($this->offerId);
    }
}
