<?php
// app/Services/Billing/BillAdjustmentService.php

namespace App\Services\Billing;

use App\Models\DiscountAssignment;
use App\Models\ScholarshipAssignment;
use App\Models\SchoolBillModel;
use Illuminate\Support\Collection;

class BillAdjustmentService
{
    /* -----------------------------------------------------------------
     |  Active assignment lookup (pre-load friendly to avoid N+1)
     | ----------------------------------------------------------------- */

    public function getActiveScholarshipAssignment(int $studentId, $preloaded = null)
    {
        if ($preloaded !== null) {
            return $preloaded;
        }

        $now = now();

        return ScholarshipAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->with('scholarship')
            ->first();
    }

    public function getActiveDiscountAssignments(int $studentId, $preloaded = null): Collection
    {
        if ($preloaded !== null) {
            return $preloaded instanceof Collection ? $preloaded : collect($preloaded);
        }

        $now = now();

        return DiscountAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now);
            })
            ->with('discount')
            ->get()
            ->sortBy(fn ($a) => $a->discount->stacking_priority ?? 999)
            ->values();
    }

    /* -----------------------------------------------------------------
     |  Scholarship deduction
     | ----------------------------------------------------------------- */

    public function getScholarshipDeduction(int $studentId, float $billAmount, $preloaded = null): array
    {
        $assignment = $this->getActiveScholarshipAssignment($studentId, $preloaded);

        if (!$assignment || !$assignment->scholarship) {
            return $this->emptyScholarship();
        }

        $scholarship = $assignment->scholarship;
        $valueType   = $assignment->value_type ?? $scholarship->value_type;
        $value       = $assignment->value      ?? $scholarship->value;
        $cap         = $assignment->cap_amount ?? $scholarship->cap_amount;

        return [
            'deduction'  => round($this->calc($valueType, $value, $billAmount, $cap), 2),
            'label'      => $scholarship->title ?? 'Scholarship',
            'value_type' => $valueType,
            'value'      => $value,
            'assignment' => $assignment,
        ];
    }

    /* -----------------------------------------------------------------
     |  Discount deduction (stacking + priority respected)
     | ----------------------------------------------------------------- */

    public function getDiscountDeduction(
        int $studentId,
        int $billId,
        float $billAmount,
        float $scholarshipDeduction = 0,
        $preloaded = null
    ): array {
        $assignments = $this->getActiveDiscountAssignments($studentId, $preloaded);
        $bill        = SchoolBillModel::find($billId);
        $category    = $bill->category ?? null;

        if ($bill && property_exists($bill, 'is_discount_eligible') && !$bill->is_discount_eligible) {
            return ['deduction' => 0.0, 'labels' => []];
        }

        $totalDeduction = 0.0;
        $labels         = [];
        $remaining      = max(0.0, $billAmount - $scholarshipDeduction);

        foreach ($assignments as $assignment) {
            $discount = $assignment->discount;

            // Direct / sibling assignment with no linked Discount record
            if (!$discount) {
                $deduction = $this->calc($assignment->value_type, $assignment->value, $remaining, $assignment->max_amount);
                $totalDeduction += $deduction;
                $labels[] = 'Sibling / Special Discount';
                $remaining -= $deduction;
                if ($remaining <= 0) break;
                continue;
            }

            if ($discount->status !== 'active') continue;
            if (!$discount->appliesToBill($billId, $category)) continue;
            if (!$discount->stackable_with_other_discounts && $totalDeduction > 0) continue;
            if (!$discount->stackable_with_scholarship && $scholarshipDeduction > 0) continue;

            $valueType = $assignment->value_type ?? $discount->value_type;
            $value     = $assignment->value      ?? $discount->value;
            $cap       = $assignment->max_amount ?? $discount->max_amount;

            $deduction = $this->calc($valueType, $value, $remaining, $cap);

            $totalDeduction += $deduction;
            $labels[] = $discount->title;
            $remaining -= $deduction;

            if ($remaining <= 0) break;
        }

        return ['deduction' => round($totalDeduction, 2), 'labels' => $labels];
    }

    /* -----------------------------------------------------------------
     |  Canonical adjustment. The ONLY place this math happens.
     |  Called by: getPaymentDetailsAjax (display), store()/bulkStore()
     |  (recomputed server-side, never trusted from client), invoice(),
     |  statement(), and AnalysisReportController (for every eligible
     |  bill, regardless of whether a payment book row exists yet).
     | ----------------------------------------------------------------- */

    public function buildBillAdjustment(
        int $studentId,
        int $billId,
        float $originalAmount,
        $scholarshipAssignment = null,
        $discountAssignments = null
    ): array {
        $scholarship = $this->getScholarshipDeduction($studentId, $originalAmount, $scholarshipAssignment);
        $discount    = $this->getDiscountDeduction($studentId, $billId, $originalAmount, $scholarship['deduction'], $discountAssignments);

        $totalDeduction = $scholarship['deduction'] + $discount['deduction'];
        $adjustedAmount = max(0.0, $originalAmount - $totalDeduction);

        return [
            'original_amount'       => round($originalAmount, 2),
            'scholarship_deduction' => $scholarship['deduction'],
            'scholarship_label'     => $scholarship['label'],
            'discount_deduction'    => $discount['deduction'],
            'discount_labels'       => $discount['labels'],
            'total_savings'         => round($totalDeduction, 2),
            'adjusted_amount'       => round($adjustedAmount, 2),
        ];
    }

    /* -----------------------------------------------------------------
     |  Helpers
     | ----------------------------------------------------------------- */

    protected function calc(?string $type, $value, float $base, $cap = null): float
    {
        if ($type === 'percentage') {
            $d = $base * (float) $value / 100;
            return $cap ? min($d, (float) $cap) : $d;
        }
        return min((float) $value, $base);
    }

    protected function emptyScholarship(): array
    {
        return ['deduction' => 0.0, 'label' => null, 'value_type' => null, 'value' => null, 'assignment' => null];
    }

    public function studentHasScholarship(int $studentId): bool
    {
        return $this->getActiveScholarshipAssignment($studentId) !== null;
    }

    public function studentHasDiscount(int $studentId): bool
    {
        return $this->getActiveDiscountAssignments($studentId)->isNotEmpty();
    }

    public function filterBillsByStudentStatus(Collection $bills, $studentStatusId): Collection
    {
        return $bills->filter(function ($bill) use ($studentStatusId) {
            $billStatusId = $bill->bill_status_id ?? $bill->statusId ?? $bill->billStatusId ?? null;
            return is_null($billStatusId)
                || $billStatusId === ''
                || $billStatusId == 0
                || $billStatusId == $studentStatusId;
        });
    }
}