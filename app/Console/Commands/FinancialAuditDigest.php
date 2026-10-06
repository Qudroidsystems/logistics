<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Audit\FinancialAuditService;
use App\Services\Messaging\PortalNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

/**
 * Sends a financial-audit digest to everyone who can view the audit:
 * open exceptions, change volume and income/expense for the period.
 * In-portal always; email piggybacks on PortalNotifier if configured.
 */
class FinancialAuditDigest extends Command
{
    protected $signature = 'financial-audit:digest {--weekly : Cover the last 7 days instead of the last day}';
    protected $description = 'Send a financial audit digest (open exceptions + activity) to auditors';

    public function handle(FinancialAuditService $svc): int
    {
        if (!FinancialAuditService::available()) {
            $this->warn('Financial audit tables not installed. Skipping.');
            return self::SUCCESS;
        }

        $weekly = (bool) $this->option('weekly');
        $from = ($weekly ? Carbon::now()->subDays(7) : Carbon::now()->subDay())->toDateString();
        $to = Carbon::now()->toDateString();

        $ex = $svc->exceptions()->where('status', 'open');
        $vol = $svc->changeVolume($from, $to);
        $tot = $svc->incomeExpenseTotals($from, $to);

        $title = ($weekly ? 'Weekly' : 'Daily') . ' financial audit digest';
        $lines = [
            "Open exceptions: {$ex->count()}",
            'Changes ' . ($weekly ? '(7 days)' : '(24h)') . ": {$vol['created']} created, {$vol['updated']} edited, {$vol['deleted']} deleted",
            'Income ₦' . number_format($tot['income'], 0) . ' · Expense ₦' . number_format($tot['expense'], 0) . ' · Net ₦' . number_format($tot['net'], 0),
        ];
        if ($ex->count()) {
            $top = $ex->take(5)->map(fn ($x) => '• ' . $x['title'])->implode("\n");
            $lines[] = "Needs review:\n" . $top;
        }
        $body = implode("\n", $lines);

        $userIds = [];
        try { $userIds = User::permission('View financial audit')->pluck('id')->map(fn ($v) => (int) $v)->all(); }
        catch (\Throwable $e) {}

        if ($userIds && class_exists(PortalNotifier::class)) {
            $url = Route::has('finance.audit.dashboard') ? route('finance.audit.dashboard') : url('/finance/audit');
            try {
                PortalNotifier::toUsers($userIds, $title, $body, $url, 'system', 'finaudit:' . $to . ($weekly ? ':w' : ':d'));
            } catch (\Throwable $e) {}
        }

        $this->info("Digest sent to " . count($userIds) . " auditor(s). Open exceptions: {$ex->count()}.");
        return self::SUCCESS;
    }
}
