<?php
// app/Models/Scholarship.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scholarship extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'scholarships';

    protected $fillable = [
        'scholarship_no', 'scholarship_type_id', 'title', 'description',
        'value_type', 'value', 'cap_amount', 'requires_application',
        'eligible_classes', 'eligible_status_ids', 'excluded_bill_categories',
        'effective_from', 'effective_to', 'max_recipients', 'renewal_frequency',
        'budget_amount', 'utilized_amount', 'status', 'created_by', 'approved_by', 'approved_at'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'cap_amount' => 'decimal:2',
        'budget_amount' => 'decimal:2',
        'utilized_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
        'requires_application' => 'boolean',
        'eligible_classes' => 'array',
        'eligible_status_ids' => 'array',
        'excluded_bill_categories' => 'array',
    ];

    // Relationships
    public function type()
    {
        return $this->belongsTo(ScholarshipType::class, 'scholarship_type_id');
    }

    public function assignments()
    {
        return $this->hasMany(ScholarshipAssignment::class);
    }

    public function applications()
    {
        return $this->hasMany(ScholarshipApplication::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('effective_from', '<=', now())
            ->where(function($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            });
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->whereHas('assignments', function($q) use ($studentId) {
            $q->where('student_id', $studentId)->where('status', 'active');
        });
    }

    // Check if scholarship has budget remaining
    public function hasBudgetRemaining($amount)
    {
        if (!$this->budget_amount) return true;
        return ($this->utilized_amount + $amount) <= $this->budget_amount;
    }

    // Calculate scholarship deduction for a bill amount
    public function calculateDeduction($billAmount)
    {
        if ($this->value_type === 'percentage') {
            $deduction = ($billAmount * $this->value / 100);
            if ($this->cap_amount && $deduction > $this->cap_amount) {
                $deduction = $this->cap_amount;
            }
            return $deduction;
        }

        return min($this->value, $billAmount);
    }
}
