<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Messaging\PortalNotifier;
use App\Services\Payroll\StatutoryRemittanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/** Daily: remind the bursary about government payments due in 3 days or overdue. */
class SendRemittanceReminders extends Command
{
    protected $signature = 'payroll:remittance-reminders {--days=3}';
    protected $description = 'Notify the bursary about PAYE/pension/NHF payments that are due soon or overdue';

    public function handle(StatutoryRemittanceService $svc): int
    {
        if (!Schema::hasTable('statutory_remittances')) return self::SUCCESS;
        $items = $svc->dueSoon((int) $this->option('days'));
        if ($items->isEmpty()) return self::SUCCESS;

        try { $users = User::permission('Manage remittances')->pluck('id')->all(); } catch (\Throwable $e) { $users = []; }
        if (!$users) { try { $users = User::permission('Approve payroll')->pluck('id')->all(); } catch (\Throwable $e) { $users = []; } }

        $late = $items->filter(fn ($r) => $r->isOverdue());
        $soon = $items->reject(fn ($r) => $r->isOverdue());
        $line = fn ($r) => strtoupper($r->type) . ' ' . $r->authority . ' (' . ($r->period->period_name ?? '') . ') ₦' . number_format($r->balance(), 2) . ' due ' . $r->due_date->format('d M');
        $body = trim(($late->count() ? "OVERDUE:\n" . $late->map($line)->implode("\n") . "\n\n" : '') . ($soon->count() ? "Due soon:\n" . $soon->map($line)->implode("\n") : ''));

        PortalNotifier::toUsers($users, $late->count() ? $late->count() . ' government payment(s) overdue' : $soon->count() . ' government payment(s) due soon',
            $body, route('payroll.remittances', ['status' => 'unpaid']), 'fees', 'remit-remind:' . now()->toDateString());
        $this->info("Reminded " . count($users) . " user(s) about {$items->count()} item(s).");
        return self::SUCCESS;
    }
}
