<?php

namespace App\Console\Commands;

use App\Services\Messaging\NoticeService;
use Illuminate\Console\Command;

class SendDueNotices extends Command
{
    protected $signature = 'notices:dispatch {--limit=20 : Maximum dispatches to run}';
    protected $description = 'Send school notices and reminders that are due';

    public function handle(NoticeService $notices): int
    {
        $ran = $notices->runDue((int) $this->option('limit'));
        $this->info($ran ? "Sent {$ran} notice dispatch(es)." : 'Nothing due.');
        return self::SUCCESS;
    }
}
