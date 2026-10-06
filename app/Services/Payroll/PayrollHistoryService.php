<?php

namespace App\Services\Payroll;

use App\Support\PayrollDocs;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only payroll history for one staff member (payslips, tax, pension,
 * deductions) and month-to-month comparisons for the bursar. Only approved,
 * paid or locked months are shown to staff.
 */
class PayrollHistoryService
{
    /** Published runs in a date range, oldest first. */
    public function runs(int $staffId, ?string $from = null, ?string $to = null, bool $includeDraft = false): Collection
    {
        return DB::table('payroll_runs as r')->join('payroll_periods as p', 'p.id', '=', 'r.payroll_period_id')
            ->where('r.staff_id', $staffId)
            ->whereIn('p.status', $includeDraft ? ['draft', 'processing', 'approved', 'paid', 'locked'] : PayrollDocs::PUBLISHED)
            ->when($from, fn ($q) => $q->where('p.end_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('p.start_date', '<=', $to))
            ->orderBy('p.start_date')
            ->get(['r.*', 'p.period_name', 'p.start_date', 'p.end_date', 'p.payment_date', 'p.status as period_status', 'p.locked_at']);
    }

    public function lines(array $runIds): Collection
    {
        if (!$runIds || !Schema::hasTable('payroll_run_lines')) return collect();
        return DB::table('payroll_run_lines')->whereIn('payroll_run_id', $runIds)->orderBy('sort')->get()->groupBy('payroll_run_id');
    }

    public static function yearRange(int $year): array
    {
        return ["$year-01-01", "$year-12-31"];
    }

    /** Month-by-month tax history with totals. */
    public function tax(int $staffId, string $from, string $to): array
    {
        $runs = $this->runs($staffId, $from, $to);
        $rows = $runs->map(function ($r) {
            $b = json_decode($r->tax_breakdown ?? 'null', true) ?: [];
            $reliefs = $b['reliefs'] ?? [];
            return (object) [
                'month' => Carbon::parse($r->start_date)->format('M Y'), 'period' => $r->period_name,
                'gross' => (float) $r->total_earnings, 'taxable' => (float) ($r->taxable_income ?: $r->total_earnings),
                'pension' => (float) $r->employee_pension, 'nhf' => (float) $r->nhf, 'nhia' => (float) ($r->nhia ?? 0),
                'rent_relief_annual' => (float) ($reliefs['rent'] ?? 0),
                'chargeable_annual' => (float) ($b['chargeable'] ?? $r->annual_chargeable ?? 0),
                'paye' => (float) $r->paye_tax, 'rule' => $r->tax_rule ?? ($b['rule'] ?? null),
                'state' => $r->tax_state, 'tin' => $r->tin, 'effective' => $b['effective_rate'] ?? null,
            ];
        });
        return ['rows' => $rows, 'totals' => [
            'gross' => $rows->sum('gross'), 'taxable' => $rows->sum('taxable'), 'pension' => $rows->sum('pension'),
            'nhf' => $rows->sum('nhf'), 'nhia' => $rows->sum('nhia'), 'paye' => $rows->sum('paye'), 'months' => $rows->count(),
        ], 'states' => $rows->pluck('state')->filter()->unique()->values(), 'tin' => $rows->pluck('tin')->filter()->last()];
    }

    /** Pension contributions month by month, with a running total and remittance status (phase 4). */
    public function pension(int $staffId, string $from, string $to): array
    {
        $runs = $this->runs($staffId, $from, $to);
        $remit = Schema::hasTable('statutory_remittance_lines')
            ? DB::table('statutory_remittance_lines as l')->join('statutory_remittances as s', 's.id', '=', 'l.remittance_id')
                ->where('s.type', 'pension')->whereIn('l.payroll_run_id', $runs->pluck('id'))->get(['l.payroll_run_id', 's.status', 's.paid_at', 's.reference'])->keyBy('payroll_run_id')
            : collect();
        $running = 0;
        $rows = $runs->map(function ($r) use (&$running, $remit) {
            $total = (float) $r->employee_pension + (float) $r->employer_pension;
            $running += $total;
            $rm = $remit[$r->id] ?? null;
            return (object) ['month' => Carbon::parse($r->start_date)->format('M Y'), 'base' => (float) ($r->pension_base ?? 0),
                'employee' => (float) $r->employee_pension, 'employer' => (float) $r->employer_pension, 'total' => $total, 'running' => $running,
                'pfa' => $r->pfa_name, 'rsa' => $r->rsa_pin, 'remitted' => $rm ? ($rm->status === 'paid') : null,
                'remitted_on' => $rm?->paid_at ? Carbon::parse($rm->paid_at)->format('d M Y') : null, 'reference' => $rm->reference ?? null];
        });
        return ['rows' => $rows, 'totals' => ['employee' => $rows->sum('employee'), 'employer' => $rows->sum('employer'), 'total' => $rows->sum('total')],
                'pfa' => $rows->pluck('pfa')->filter()->last(), 'rsa' => $rows->pluck('rsa')->filter()->last()];
    }

    /** Non-tax deductions (NHF, loans, cooperative, union…) by month. */
    public function deductions(int $staffId, string $from, string $to): array
    {
        $runs = $this->runs($staffId, $from, $to);
        $lines = $this->lines($runs->pluck('id')->all());
        $codes = []; $rows = [];
        foreach ($runs as $r) {
            $row = ['month' => Carbon::parse($r->start_date)->format('M Y')];
            foreach ($lines[$r->id] ?? [] as $l) {
                if ($l->type !== 'deduction' || in_array($l->code, ['PAYE', 'PENSION_EE'], true)) continue;
                $codes[$l->code] = $codes[$l->code] ?? preg_replace('/\s*\(.*\)$/', '', $l->label);
                $row[$l->code] = ($row[$l->code] ?? 0) + (float) $l->amount;
            }
            $rows[] = $row;
        }
        $totals = [];
        foreach (array_keys($codes) as $c) $totals[$c] = array_sum(array_column($rows, $c));

        $loans = Schema::hasTable('loans_advances')
            ? DB::table('loans_advances')->where('staff_id', $staffId)->orderByDesc('id')->get(['reference_no', 'type', 'amount', 'balance', 'monthly_repayment', 'status'])
            : collect();
        return ['codes' => $codes, 'rows' => $rows, 'totals' => $totals, 'loans' => $loans];
    }

    /** Year-to-date figures for the self-service home. */
    public function ytd(int $staffId, int $year): array
    {
        [$from, $to] = self::yearRange($year);
        $runs = $this->runs($staffId, $from, $to);
        return ['gross' => $runs->sum('total_earnings'), 'paye' => $runs->sum('paye_tax'), 'pension' => $runs->sum('employee_pension'),
                'employer_pension' => $runs->sum('employer_pension'), 'net' => $runs->sum('net_pay'), 'months' => $runs->count()];
    }

    /** Compare a month with the one before it. */
    public function variance(int $periodId): array
    {
        $cur = DB::table('payroll_periods')->where('id', $periodId)->first();
        $prev = $cur ? DB::table('payroll_periods')->where('start_date', '<', $cur->start_date)->orderByDesc('start_date')->first() : null;
        $name = fn ($ids) => DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->whereIn('s.id', $ids)->pluck('u.name', 's.id');

        $c = DB::table('payroll_runs')->where('payroll_period_id', $periodId)->get()->keyBy('staff_id');
        $p = $prev ? DB::table('payroll_runs')->where('payroll_period_id', $prev->id)->get()->keyBy('staff_id') : collect();
        $names = $name($c->keys()->merge($p->keys())->unique()->all());

        $lc = $this->lines($c->pluck('id')->all()); $lp = $this->lines($p->pluck('id')->all());
        $sumByCode = fn ($lines) => collect($lines)->groupBy('code')->map(fn ($g) => ['label' => $g->first()->label, 'amount' => $g->sum('amount'), 'type' => $g->first()->type]);

        $new = []; $left = []; $changed = [];
        foreach ($c as $sid => $run) {
            if (!isset($p[$sid])) { $new[] = ['staff_id' => $sid, 'name' => $names[$sid] ?? '#' . $sid, 'net' => (float) $run->net_pay]; continue; }
            $diff = round((float) $run->net_pay - (float) $p[$sid]->net_pay, 2);
            $threshold = max(1000, abs((float) $p[$sid]->net_pay) * 0.05);
            if (abs($diff) < $threshold) continue;
            $a = $sumByCode($lc[$run->id] ?? []); $b = $sumByCode($lp[$p[$sid]->id] ?? []);
            $why = [];
            foreach ($a->keys()->merge($b->keys())->unique() as $code) {
                if (($a[$code]['type'] ?? $b[$code]['type'] ?? '') === 'employer') continue;
                $d = round(($a[$code]['amount'] ?? 0) - ($b[$code]['amount'] ?? 0), 2);
                if (abs($d) >= 0.01) $why[] = ['label' => $a[$code]['label'] ?? $b[$code]['label'], 'diff' => $d];
            }
            usort($why, fn ($x, $y) => abs($y['diff']) <=> abs($x['diff']));
            $changed[] = ['staff_id' => $sid, 'name' => $names[$sid] ?? '#' . $sid, 'before' => (float) $p[$sid]->net_pay, 'after' => (float) $run->net_pay,
                          'diff' => $diff, 'why' => array_slice($why, 0, 4)];
        }
        foreach ($p as $sid => $run) if (!isset($c[$sid])) $left[] = ['staff_id' => $sid, 'name' => $names[$sid] ?? '#' . $sid, 'net' => (float) $run->net_pay];
        usort($changed, fn ($x, $y) => abs($y['diff']) <=> abs($x['diff']));

        return ['previous' => $prev, 'new' => $new, 'left' => $left, 'changed' => $changed,
                'net_before' => (float) $p->sum('net_pay'), 'net_after' => (float) $c->sum('net_pay'),
                'gross_before' => (float) $p->sum('total_earnings'), 'gross_after' => (float) $c->sum('total_earnings')];
    }
}
