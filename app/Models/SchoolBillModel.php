<?php
// app/Models/SchoolBillModel.php (COMPLETE UPDATE)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolBillModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'school_bill';

    protected $fillable = [
        'id', 'title', 'description', 'bill_amount', 'statusId',
        'effective_from', 'effective_to', 'is_mandatory', 'due_date',
        'late_fee', 'late_fee_type', 'payment_frequency', 'grace_period_days',
        'is_scholarship_eligible', 'is_discount_eligible', 'max_discount_percentage',
        'category', 'priority', 'attachment', 'is_active'
    ];

    protected $casts = [
        'bill_amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'is_scholarship_eligible' => 'boolean',
        'is_discount_eligible' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function classAssignments()
    {
        return $this->hasMany(SchoolBillTermSession::class, 'bill_id');
    }

    public function studentPayments()
    {
        return $this->hasMany(StudentBillPayment::class, 'school_bill_id');
    }

    public function status()
    {
        return $this->belongsTo(StudentStatus::class, 'statusId');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEffective($query)
    {
        return $query->where(function($q) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', now());
        })->where(function($q) {
            $q->whereNull('effective_to')
              ->orWhere('effective_to', '>=', now());
        });
    }

    public function scopeForStudentStatus($query, $statusId)
    {
        return $query->where('statusId', $statusId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Calculate due date with late fee
    public function calculateLateFee($paymentDate = null)
    {
        $paymentDate = $paymentDate ?? now();

        if (!$this->due_date || $paymentDate <= $this->due_date) {
            return 0;
        }

        $daysLate = $paymentDate->diffInDays($this->due_date);

        if ($daysLate <= $this->grace_period_days) {
            return 0;
        }

        if ($this->late_fee_type === 'percentage') {
            return ($this->bill_amount * $this->late_fee) / 100;
        }

        return $this->late_fee;
    }

    // Get adjusted amount for a specific student
    public function getAdjustedAmountForStudent($studentId, $termId, $sessionId, $classId = null)
    {
        $originalAmount = $this->bill_amount;
        $lateFee = $this->calculateLateFee();

        $originalAmount += $lateFee;

        // Get student's active scholarships
        $scholarshipAssignments = ScholarshipAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', now())
            ->where(function($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->with('scholarship')
            ->get();

        $totalScholarshipDeduction = 0;
        foreach ($scholarshipAssignments as $assignment) {
            // Check if scholarship applies to this bill
            if ($assignment->scholarship && $assignment->scholarship->excluded_bill_categories) {
                $excludedCategories = json_decode($assignment->scholarship->excluded_bill_categories, true) ?? [];
                if (in_array($this->category, $excludedCategories)) {
                    continue;
                }
            }

            if ($assignment->value_type === 'percentage') {
                $deduction = ($originalAmount * $assignment->value / 100);
                if ($assignment->cap_amount && $deduction > $assignment->cap_amount) {
                    $deduction = $assignment->cap_amount;
                }
            } else {
                $remaining = $originalAmount - $totalScholarshipDeduction;
                $deduction = min($assignment->value, $remaining);
            }
            $totalScholarshipDeduction += $deduction;
        }

        $afterScholarship = max(0, $originalAmount - $totalScholarshipDeduction);

        // Get student's active discounts
        $discountAssignments = DiscountAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', now())
            ->where(function($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->with('discount')
            ->get();

        $totalDiscountDeduction = 0;
        foreach ($discountAssignments as $assignment) {
            if ($assignment->discount) {
                // Check applicability
                if ($assignment->discount->applicable_to === 'specific_bills') {
                    $applicableBills = json_decode($assignment->discount->applicable_bill_ids, true) ?? [];
                    if (!in_array($this->id, $applicableBills)) continue;
                }

                if ($assignment->discount->applicable_to === 'specific_categories') {
                    $applicableCategories = json_decode($assignment->discount->applicable_categories, true) ?? [];
                    if (!in_array($this->category, $applicableCategories)) continue;
                }

                // Check stacking rules
                if (!$assignment->discount->stackable_with_scholarship && $totalScholarshipDeduction > 0) {
                    continue;
                }

                if ($assignment->value_type === 'percentage') {
                    $deduction = ($afterScholarship * $assignment->value / 100);
                    if ($assignment->max_amount && $deduction > $assignment->max_amount) {
                        $deduction = $assignment->max_amount;
                    }
                } else {
                    $remaining = $afterScholarship - $totalDiscountDeduction;
                    $deduction = min($assignment->value, $remaining);
                }
                $totalDiscountDeduction += $deduction;
            }
        }

        $adjustedAmount = max(0, $afterScholarship - $totalDiscountDeduction);

        return [
            'original_amount' => $originalAmount,
            'late_fee' => $lateFee,
            'scholarship_deduction' => $totalScholarshipDeduction,
            'discount_deduction' => $totalDiscountDeduction,
            'adjusted_amount' => $adjustedAmount,
            'savings' => $totalScholarshipDeduction + $totalDiscountDeduction
        ];
    }
}
