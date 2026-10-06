<?php

namespace App\Console\Commands;

use App\Services\Messaging\ResultSendService;
use Illuminate\Console\Command;

class SendResultBatches extends Command
{
    protected $signature = 'results:send {--max=60 : Maximum students per run}';
    protected $description = 'Generate and send queued report cards to parents';

    public function handle(ResultSendService $service): int
    {
        $n = $service->runDue((int) $this->option('max'));
        $this->info($n ? "Processed {$n} student(s)." : 'Nothing queued.');
        return self::SUCCESS;
    }
}
