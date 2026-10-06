<?php

namespace App\Console\Commands;

use App\Services\Leave\LeaveReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SendLeaveReminders extends Command
{
    protected $signature = 'leave:reminders';
    protected $description = 'Send leave start / countdown / resumption reminders and flag staff who have not resumed';

    public function handle(LeaveReminderService $svc): int
    {
        if (!Schema::hasTable('leave_reminder_logs')) return self::SUCCESS;
        $r = $svc->run();
        $this->info("Sent {$r['sent']} reminder(s); {$r['resumed']} staff marked as resumed.");
        return self::SUCCESS;
    }
}
