<?php

namespace App\Services;

use App\Models\ResultAccessException;
use App\Models\ResultAccessSetting;
use App\Models\Student;
use App\Services\Billing\StudentFeeStatementService;

/**
 * Decides whether a student may see their results for a term.
 *
 * Order of checks:
 *  1. Manual block (studentRegistration.can_view_assessments = false)
 *     -> blocked, and admin fee exceptions do NOT override it.
 *  2. Fee blocking switched off in settings -> allowed.
 *  3. Mock report and "apply to mock" off -> allowed.
 *  4. Work out what the student owes (same figures as My Payments /
 *     the bursar), per the settings' debt scope and threshold.
 *     Not over the threshold -> allowed.
 *  5. An active admin exception covering this term -> allowed.
 *  6. Otherwise -> blocked for owing fees.
 */
class ResultAccessService
{
    public function __construct(protected StudentFeeStatementService $statements) {}

    public function check(int $studentId, ?int $termId, ?int $sessionId, bool $isMock = false): array
    {
        $settings = ResultAccessSetting::current();
        $student  = Student::find($studentId);

        $result = [
            'allowed'   => true,
            'reason'    => 'ok',          // ok | manual_block | owing
            'owed'      => 0.0,
            'payable'   => 0.0,
            'term_owed' => 0.0,
            'arrears'   => 0.0,
            'exception' => null,
            'message'   => null,
            'settings'  => $settings,
        ];

        if (!$student) {
            return ['allowed' => false, 'reason' => 'manual_block', 'message' => 'Student record not found.'] + $result;
        }

        if (isset($student->can_view_assessments) && !$student->can_view_assessments) {
            return array_merge($result, [
                'allowed' => false,
                'reason'  => 'manual_block',
                'message' => 'Access to your results has been restricted by the school. Please contact the school office.',
            ]);
        }

        // Report cards must be approved (released) first, when the school requires it.
        if (!$isMock && $termId && $sessionId && \App\Services\Reporting\ReportApprovalService::enforced()
            && !app(\App\Services\Reporting\ReportApprovalService::class)->isReleased($studentId, $termId, $sessionId)) {
            return array_merge($result, [
                'allowed' => false,
                'reason'  => 'not_released',
                'message' => 'Results for this term have not been released yet. Please check back later.',
            ]);
        }

        if (!$settings->enabled || !$termId || !$sessionId) {
            return $result;
        }
        if ($isMock && !$settings->apply_to_mock) {
            return $result;
        }

        $debt = $this->debtFor($student, $termId, $sessionId, $settings);
        $result = array_merge($result, $debt);

        if (!$this->overThreshold($debt['owed'], $debt['payable'], $settings)) {
            return $result;
        }

        // On an instalment plan and up to date with it -> allowed (if the plan says so).
        if (\App\Services\Billing\InstalmentPlanService::available()) {
            $sched = app(\App\Services\Billing\InstalmentPlanService::class)
                ->schedule($studentId, $termId, $sessionId, $debt['payable'] - $debt['arrears'], ($debt['payable'] - $debt['arrears']) - $debt['term_owed']);
            if ($sched && $sched['plan']->results_when_on_track && $sched['on_track'] && $debt['arrears'] <= 0.009) {
                $result['plan'] = $sched;
                return $result;
            }
        }

        $exception = ResultAccessException::where('student_id', $studentId)
            ->active()
            ->covering($termId, $sessionId)
            ->orderByDesc('id')
            ->first();

        if ($exception) {
            $result['exception'] = $exception;
            return $result;
        }

        $result['allowed'] = false;
        $result['reason']  = 'owing';
        $result['message'] = $settings->blocked_message
            ?: 'Your results for this term are on hold because there is an outstanding fee balance. Please clear the balance or speak to the school bursary.';

        return $result;
    }

    /** Amount owed and payable, according to the debt scope. */
    public function debtFor(Student $student, int $termId, int $sessionId, ?ResultAccessSetting $settings = null): array
    {
        $settings  = $settings ?? ResultAccessSetting::current();
        $statement = $this->statements->buildStatement($student, $termId, $sessionId);

        $termOwed    = (float) ($statement['totals']['outstanding'] ?? 0);
        $termPayable = (float) ($statement['totals']['adjusted'] ?? 0);
        $arrears     = $settings->debt_scope === 'term_and_arrears'
            ? (float) ($statement['arrears']['total_arrears'] ?? 0)
            : 0.0;

        return [
            'term_owed' => round($termOwed, 2),
            'arrears'   => round($arrears, 2),
            'owed'      => round($termOwed + $arrears, 2),
            'payable'   => round($termPayable + $arrears, 2),
        ];
    }

    public function overThreshold(float $owed, float $payable, ResultAccessSetting $settings): bool
    {
        if ($owed <= 0.009) return false;

        return match ($settings->threshold_type) {
            'amount'  => $owed > (float) $settings->threshold_value,
            'percent' => $payable > 0 && ($owed / $payable * 100) > (float) $settings->threshold_value,
            default   => true,
        };
    }
}
