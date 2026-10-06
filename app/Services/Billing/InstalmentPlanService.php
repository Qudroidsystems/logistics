<?php

namespace App\Services\Billing;

use App\Models\FeeInstalmentPlan;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Works out a student's instalment position for a term.
 *
 * All money is handled in kobo (integers). Each step's amount is its % of the
 * term's payable fees (after scholarships/discounts); the last step takes the
 * rounding remainder so the steps always add up to the payable exactly.
 * Payments are applied to steps in due-date order.
 */
class InstalmentPlanService
{
    protected array $planCache = [];

    public function __construct(protected StudentFeeStatementService $statements) {}

    public static function available(): bool
    {
        static $ok = null;
        return $ok ??= Schema::hasTable('fee_instalment_plans');
    }

    /** The plan covering this student for the term (explicit > class > all), or null. */
    public function planFor(int $studentId, int $termId, int $sessionId, ?int $classId = null): ?FeeInstalmentPlan
    {
        if (!self::available() || !$termId || !$sessionId) return null;

        $key = "$studentId:$termId:$sessionId";
        if (array_key_exists($key, $this->planCache)) return $this->planCache[$key];

        $plans = FeeInstalmentPlan::where('term_id', $termId)->where('session_id', $sessionId)
            ->where('is_active', true)->orderByDesc('id')->get();
        if ($plans->isEmpty()) return $this->planCache[$key] = null;

        $assigned = DB::table('fee_instalment_assignments')->where('student_id', $studentId)
            ->whereIn('plan_id', $plans->pluck('id'))->orderByDesc('id')->value('plan_id');
        if ($assigned) return $this->planCache[$key] = $plans->firstWhere('id', $assigned);

        $classId ??= $this->statements->resolveClassId($studentId, $termId, $sessionId);
        $byClass = $plans->first(fn ($p) => $p->applies_to === 'classes' && $classId && in_array((int) $classId, array_map('intval', $p->class_ids ?? []), true));
        if ($byClass) return $this->planCache[$key] = $byClass;

        return $this->planCache[$key] = $plans->firstWhere('applies_to', 'all');
    }

    /**
     * Schedule for a student. Pass payable/paid (naira) when you already have
     * them from a statement, to avoid rebuilding it.
     *
     * @return array|null {plan, steps[], payable, paid, overdue, due_now, next, on_track, balance}
     */
    public function schedule(int $studentId, int $termId, int $sessionId, ?float $payable = null, ?float $paid = null, ?int $classId = null): ?array
    {
        $plan = $this->planFor($studentId, $termId, $sessionId, $classId);
        if (!$plan) return null;

        if ($payable === null || $paid === null) {
            $student = Student::find($studentId);
            if (!$student) return null;
            $st = $this->statements->buildStatement($student, $termId, $sessionId);
            $payable = (float) ($st['totals']['adjusted'] ?? 0);
            $paid    = (float) ($st['totals']['paid'] ?? 0);
        }

        return $this->compute($plan, $payable, $paid);
    }

    public function compute(FeeInstalmentPlan $plan, float $payable, float $paid, ?Carbon $today = null): array
    {
        $today       = ($today ?? now())->copy()->startOfDay();
        $payableKobo = max(0, (int) round($payable * 100));
        $paidLeft    = max(0, (int) round($paid * 100));
        $steps       = $plan->steps();
        $n           = count($steps);

        $out = []; $allocated = 0; $overdue = 0; $next = null;
        foreach ($steps as $i => $s) {
            $amount = $i === $n - 1
                ? $payableKobo - $allocated
                : (int) floor($payableKobo * (float) $s['percent'] / 100);
            $allocated += $amount;

            $covered  = min($amount, $paidLeft);
            $paidLeft -= $covered;
            $remain   = $amount - $covered;
            $due      = !empty($s['due_date']) ? Carbon::parse($s['due_date'])->startOfDay() : null;
            $isPast   = $due && $due->lt($today);

            $status = $remain <= 0 ? 'paid' : ($isPast ? 'overdue' : ($covered > 0 ? 'partial' : 'upcoming'));
            if ($status === 'overdue') $overdue += $remain;
            if ($remain > 0 && !$isPast && !$next) {
                $next = ['label' => $s['label'], 'due_date' => $due?->format('d M Y'), 'amount' => $remain / 100, 'days' => $due ? $today->diffInDays($due) : null];
            }

            $out[] = [
                'label'    => $s['label'] ?: 'Instalment ' . ($i + 1),
                'percent'  => (float) $s['percent'],
                'due_date' => $due?->format('d M Y'),
                'amount'   => $amount / 100,
                'paid'     => $covered / 100,
                'balance'  => $remain / 100,
                'status'   => $status,
            ];
        }

        return [
            'plan'     => $plan,
            'steps'    => $out,
            'payable'  => $payableKobo / 100,
            'paid'     => min($payableKobo, (int) round($paid * 100)) / 100,
            'balance'  => max(0, $payableKobo - (int) round($paid * 100)) / 100,
            'overdue'  => $overdue / 100,
            'due_now'  => $overdue / 100,   // amount needed to be up to date today
            'next'     => $next,
            'on_track' => $overdue <= 0,
        ];
    }

    /** Validate & normalise schedule rows from a form. Returns [rows, error]. */
    public static function normaliseSchedule(array $labels, array $percents, array $dates): array
    {
        $rows = [];
        foreach ($percents as $i => $p) {
            if ($p === null || $p === '') continue;
            $rows[] = [
                'label'    => trim((string) ($labels[$i] ?? '')) ?: 'Instalment ' . (count($rows) + 1),
                'percent'  => round((float) $p, 2),
                'due_date' => !empty($dates[$i]) ? Carbon::parse($dates[$i])->format('Y-m-d') : null,
            ];
        }
        if (count($rows) < 2) return [[], 'Add at least two instalments.'];
        foreach ($rows as $r) {
            if ($r['percent'] <= 0) return [[], 'Each instalment must be more than 0%.'];
            if (!$r['due_date']) return [[], 'Each instalment needs a due date.'];
        }
        $sum = round(array_sum(array_column($rows, 'percent')), 2);
        if (abs($sum - 100) > 0.001) return [[], "The instalments add up to {$sum}% — they must add up to exactly 100%."];

        usort($rows, fn ($a, $b) => strcmp($a['due_date'], $b['due_date']));
        return [$rows, null];
    }
}
