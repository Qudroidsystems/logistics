<?php
// app/Models/Discount.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discounts';

    protected $fillable = [
        'discount_no', 'discount_type_id', 'title', 'description',
        'value_type', 'value', 'max_amount', 'applicable_to',
        'applicable_bill_ids', 'applicable_categories', 'eligible_classes',
        'condition_type', 'condition_value', 'days_before_due',
        'stackable_with_scholarship', 'stackable_with_other_discounts',
        'stacking_priority', 'effective_from', 'effective_to',
        'status', 'created_by', 'approved_by', 'approved_at'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'condition_value' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
        'stackable_with_scholarship' => 'boolean',
        'stackable_with_other_discounts' => 'boolean',
        'applicable_bill_ids' => 'array',
        'applicable_categories' => 'array',
        'eligible_classes' => 'array',
    ];

    // Relationships
    public function type()
    {
        return $this->belongsTo(DiscountType::class, 'discount_type_id');
    }

    public function assignments()
    {
        return $this->hasMany(DiscountAssignment::class);
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

    // Check if discount applies to a bill
    public function appliesToBill($billId, $billCategory = null)
    {
        if ($this->applicable_to === 'specific_bills') {
            $applicableBills = json_decode($this->applicable_bill_ids, true) ?? [];
            return in_array($billId, $applicableBills);
        }

        if ($this->applicable_to === 'specific_categories') {
            $applicableCategories = json_decode($this->applicable_categories, true) ?? [];
            return in_array($billCategory, $applicableCategories);
        }

        return true; // 'all_bills'
    }

    // Calculate discount deduction
    public function calculateDeduction($amount, $existingDiscountsTotal = 0)
    {
        $remaining = max(0, $amount - $existingDiscountsTotal);

        if ($this->value_type === 'percentage') {
            $deduction = ($remaining * $this->value / 100);
            if ($this->max_amount && $deduction > $this->max_amount) {
                $deduction = $this->max_amount;
            }
            return $deduction;
        }

        return min($this->value, $remaining);
    }
}
