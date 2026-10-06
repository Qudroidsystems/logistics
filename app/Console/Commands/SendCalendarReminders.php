<?php

namespace App\Console\Commands;

use App\Services\Calendar\CalendarReminderService;
use Illuminate\Console\Command;

class SendCalendarReminders extends Command
{
    protected $signature = 'calendar:reminders';
    protected $description = 'Send due school-calendar reminders (in-portal, email, SMS, WhatsApp)';

    public function handle(CalendarReminderService $svc): int
    {
        if (!CalendarReminderService::available()) {
            $this->warn('Calendar tables not installed. Skipping.');
            return self::SUCCESS;
        }
        $r = $svc->run();
        $s = $r['sent'];
        $this->info(sprintf('Checked %d event(s). Sent — portal: %d, email: %d, sms: %d, whatsapp: %d.',
            $r['events'], $s['portal'] ?? 0, $s['email'] ?? 0, $s['sms'] ?? 0, $s['whatsapp'] ?? 0));
        return self::SUCCESS;
    }
}
