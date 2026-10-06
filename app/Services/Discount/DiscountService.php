<?php
// app/Services/Discount/DiscountService.php

namespace App\Services\Discount;

use App\Models\Discount;
use App\Models\DiscountAssignment;
use App\Models\SiblingGroup;
use App\Models\Student;
use App\Models\SchoolBillModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DiscountService
{
    /**
     * Apply discounts to a student's bill
     */
    public function applyDiscountsToBill($studentId, $billId, $termId, $sessionId, $currentAmount, $existingDeductions = 0)
    {
        $bill = SchoolBillModel::findOrFail($billId);

        // Check if bill is discount eligible
        if (!$bill->is_discount_eligible) {
            return [
                'applied' => false,
                'deduction' => 0,
                'applied_discounts' => []
            ];
        }

        // Get active discount assignments sorted by priority
        $discountAssignments = DiscountAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', now())
            ->where(function($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->with('discount')
            ->get()
            ->sortBy(function($assignment) {
                return $assignment->discount->stacking_priority ?? 999;
            });

        $totalDeduction = 0;
        $appliedDiscounts = [];
        $remainingAmount = $currentAmount - $existingDeductions;

        foreach ($discountAssignments as $assignment) {
            $discount = $assignment->discount;

            if (!$discount) continue;

            // Check if discount applies to this bill
            if (!$discount->appliesToBill($billId, $bill->category)) {
                continue;
            }

            // Check stacking rules
            if (!$discount->stackable_with_other_discounts && $totalDeduction > 0) {
                continue;
            }

            if ($remainingAmount <= 0) break;

            // Calculate deduction
            $deduction = $discount->calculateDeduction($remainingAmount, $totalDeduction);

            $totalDeduction += $deduction;
            $remainingAmount -= $deduction;

            $appliedDiscounts[] = [
                'discount_id' => $discount->id,
                'title' => $discount->title,
                'value_type' => $discount->value_type,
                'value' => $discount->value,
                'deduction' => $deduction
            ];
        }

        return [
            'applied' => $totalDeduction > 0,
            'deduction' => $totalDeduction,
            'applied_discounts' => $appliedDiscounts
        ];
    }

    /**
     * Apply sibling discount to a family
     */
    public function applySiblingDiscount($familyId, $termId, $sessionId)
    {
        $siblingGroup = SiblingGroup::findOrFail($familyId);
        $students = $siblingGroup->students;

        $results = [];

        foreach ($students as $student) {
            $discountPercentage = $siblingGroup->calculateSiblingDiscount($student->id);

            // Create or update discount assignment
            $assignment = DiscountAssignment::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'sibling_group_id' => $siblingGroup->id,
                    'discount_id' => null, // Direct sibling discount
                ],
                [
                    'value_type' => 'percentage',
                    'value' => $discountPercentage,
                    'status' => 'active',
                    'effective_from' => now(),
                    'effective_to' => now()->addYear(),
                    'assigned_by' => Auth::id(),
                ]
            );

            $results[] = [
                'student_id' => $student->id,
                'student_name' => $student->firstname . ' ' . $student->lastname,
                'discount_percentage' => $discountPercentage,
                'assignment_id' => $assignment->id
            ];
        }

        return $results;
    }

    /**
     * Apply early payment discount
     */
    public function applyEarlyPaymentDiscount($studentId, $billId, $paymentDate = null)
    {
        $paymentDate = $paymentDate ?? now();
        $bill = SchoolBillModel::findOrFail($billId);

        if (!$bill->due_date || $paymentDate > $bill->due_date) {
            return [
                'eligible' => false,
                'reason' => 'Payment is not early or due date not set',
                'discount' => 0
            ];
        }

        $daysEarly = $bill->due_date->diffInDays($paymentDate);

        // Get early payment discount type
        $discountType = Discount::where('condition_type', 'early_payment')
            ->where('days_before_due', '>=', $daysEarly)
            ->active()
            ->first();

        if (!$discountType) {
            return [
                'eligible' => false,
                'reason' => 'No early payment discount configured',
                'discount' => 0
            ];
        }

        $discountAmount = $discountType->value_type === 'percentage'
            ? ($bill->bill_amount * $discountType->value / 100)
            : min($discountType->value, $bill->bill_amount);

        return [
            'eligible' => true,
            'discount_type' => $discountType->title,
            'discount' => $discountAmount
        ];
    }

    /**
     * Get discount summary for a student
     */
    public function getStudentDiscountSummary($studentId)
    {
        $activeAssignments = DiscountAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', now())
            ->where(function($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->with('discount', 'siblingGroup')
            ->get();

        $totalSavings = 0;
        $summary = [];

        foreach ($activeAssignments as $assignment) {
            if ($assignment->discount) {
                $totalSavings += $assignment->value;
                $summary[] = [
                    'type' => 'discount',
                    'name' => $assignment->discount->title,
                    'value_type' => $assignment->value_type,
                    'value' => $assignment->value,
                    'effective_from' => $assignment->effective_from,
                    'effective_to' => $assignment->effective_to,
                ];
            } elseif ($assignment->siblingGroup) {
                $totalSavings += $assignment->value;
                $summary[] = [
                    'type' => 'sibling_discount',
                    'name' => 'Sibling Discount - ' . $assignment->siblingGroup->family_name,
                    'value_type' => $assignment->value_type,
                    'value' => $assignment->value,
                    'effective_from' => $assignment->effective_from,
                    'effective_to' => $assignment->effective_to,
                ];
            }
        }

        return [
            'has_discounts' => $activeAssignments->isNotEmpty(),
            'total_active' => $activeAssignments->count(),
            'total_savings' => $totalSavings,
            'discounts' => $summary
        ];
    }
}
