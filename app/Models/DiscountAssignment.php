<?php
// app/Models/DiscountAssignment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discount_assignments';

    protected $fillable = [
        'discount_id', 'student_id', 'value_type', 'value', 'max_amount',
        'sibling_group_id', 'sibling_count', 'per_child_discount',
        'status', 'effective_from', 'effective_to', 'reason', 'assigned_by'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'per_child_discount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    // Relationships
    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function siblingGroup()
    {
        return $this->belongsTo(SiblingGroup::class, 'sibling_group_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
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

    // Check if assignment is active
    public function isActive()
    {
        return $this->status === 'active'
            && $this->effective_from <= now()
            && ($this->effective_to === null || $this->effective_to >= now());
    }
}
