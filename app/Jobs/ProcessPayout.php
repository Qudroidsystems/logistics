<?php

namespace App\Jobs;

use App\Modules\Settlements\PayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;
    public array $backoff = [30, 300];

    public function __construct(public int $payoutId)
    {
    }

    public function handle(PayoutService $payouts): void
    {
        $payouts->send($this->payoutId);
    }
}
