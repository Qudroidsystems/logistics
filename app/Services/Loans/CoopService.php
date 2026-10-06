<?php

namespace App\Services\Loans;

use App\Models\CoopMember;
use App\Models\CoopTransaction;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Staff cooperative: monthly savings through payroll, withdrawals, dividends. */
class CoopService
{
    public static function available(): bool
    {
        return Schema::hasTable('coop_members') && Schema::hasTable('coop_transactions');
    }

    /** Payroll deduction line for an active member (used by PayrollEngine). */
    public function deductionFor(int $staffId): array
    {
        if (!self::available()) return [];
        $m = CoopMember::where('staff_id', $staffId)->where('status', 'active')->first();
        if (!$m || $m->monthly_contribution <= 0) return [];
        return [['code' => 'COOPERATIVE', 'label' => 'Cooperative savings', 'amount' => round($m->monthly_contribution, 2), 'meta' => ['coop' => true]]];
    }

    public function balance(int $staffId): float
    {
        return round((float) CoopTransaction::where('staff_id', $staffId)->sum('amount'), 2);
    }

    public function join(int $staffId, float $monthly, ?string $note = null): CoopMember
    {
        return CoopMember::updateOrCreate(['staff_id' => $staffId], [
            'monthly_contribution' => round($monthly, 2), 'status' => 'active', 'left_on' => null,
            'joined_on' => CoopMember::where('staff_id', $staffId)->value('joined_on') ?? now()->toDateString(), 'note' => $note,
        ]);
    }

    public function setStatus(CoopMember $m, string $status): void
    {
        $m->update(['status' => $status, 'left_on' => $status === 'left' ? now()->toDateString() : null]);
    }

    /** Record savings deducted in a locked payroll month (idempotent per month). */
    public function payrollContributions(PayrollPeriod $period, ?int $userId = null): int
    {
        if (!self::available()) return 0;
        $rows = DB::table('payroll_run_lines')->where('payroll_period_id', $period->id)->where('code', 'COOPERATIVE')
            ->groupBy('staff_id')->selectRaw('staff_id, SUM(amount) as amt')->get();
        $n = 0;
        foreach ($rows as $r) {
            if ((float) $r->amt <= 0) continue;
            $exists = CoopTransaction::where('staff_id', $r->staff_id)->where('payroll_period_id', $period->id)->where('type', 'contribution')->exists();
            if ($exists) continue;
            CoopTransaction::create([
                'staff_id' => $r->staff_id, 'type' => 'contribution', 'amount' => round((float) $r->amt, 2), 'payroll_period_id' => $period->id,
                'txn_date' => Carbon::parse($period->end_date)->toDateString(), 'reference' => $period->period_name, 'recorded_by' => $userId,
            ]);
            $n++;
        }
        return $n;
    }

    public function record(int $staffId, string $type, float $amount, int $userId, ?string $date = null, ?string $reference = null, ?string $note = null): CoopTransaction
    {
        $signed = in_array($type, ['withdrawal'], true) ? -abs($amount) : ($type === 'adjustment' ? $amount : abs($amount));
        if ($type === 'withdrawal' && abs($amount) > $this->balance($staffId) + 0.004) {
            throw new \RuntimeException('Withdrawal is more than the savings balance (₦' . number_format($this->balance($staffId), 2) . ').');
        }
        $t = CoopTransaction::create([
            'staff_id' => $staffId, 'type' => $type, 'amount' => round($signed, 2), 'txn_date' => $date ?: now()->toDateString(),
            'reference' => $reference, 'note' => $note, 'recorded_by' => $userId,
        ]);
        if (class_exists(\App\Services\Accounting\LedgerPoster::class)) {
            try { app(\App\Services\Accounting\LedgerPoster::class)->coopTransaction($t); } catch (\Throwable $e) {}
        }
        return $t;
    }

    /** Share a dividend pool across members in proportion to their savings. */
    public function dividend(float $pool, int $userId, ?string $note = null): int
    {
        $balances = CoopTransaction::groupBy('staff_id')->selectRaw('staff_id, SUM(amount) as bal')->having('bal', '>', 0)->pluck('bal', 'staff_id');
        $total = (float) $balances->sum();
        if ($pool <= 0 || $total <= 0) throw new \RuntimeException('Nothing to share.');
        $n = 0;
        foreach ($balances as $staffId => $bal) {
            $share = round($pool * (float) $bal / $total, 2);
            if ($share <= 0) continue;
            $this->record((int) $staffId, 'dividend', $share, $userId, null, 'DIV-' . now()->format('Y'), $note);
            $n++;
        }
        return $n;
    }

    public function statement(int $staffId): array
    {
        $rows = CoopTransaction::with('period:id,period_name')->where('staff_id', $staffId)->orderBy('txn_date')->orderBy('id')->get();
        $run = 0.0;
        foreach ($rows as $r) { $run += (float) $r->amount; $r->running = round($run, 2); }
        return ['rows' => $rows->reverse()->values(), 'balance' => round($run, 2),
                'member' => CoopMember::where('staff_id', $staffId)->first()];
    }
}
