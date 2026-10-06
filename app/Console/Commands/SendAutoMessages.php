<?php

namespace App\Console\Commands;

use App\Services\Messaging\AutoMessageService;
use Illuminate\Console\Command;

class SendAutoMessages extends Command
{
    protected $signature = 'messages:auto';
    protected $description = 'Send due absence alerts, fee reminders and birthday wishes';

    public function handle(AutoMessageService $auto): int
    {
        $done = $auto->runDue();
        $this->info($done ? json_encode($done) : 'Nothing due.');
        return self::SUCCESS;
    }
}
