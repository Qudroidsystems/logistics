<?php
// app/Models/StaffSalaryStructure.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffSalaryStructure extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff_salary_structures';

    protected $fillable = [
        'staff_id', 'effective_from', 'effective_to', 'basic_salary',
        'housing_allowance', 'transport_allowance', 'meal_allowance',
        'medical_allowance', 'utility_allowance', 'other_allowances',
        'custom_allowances', 'is_active', 'created_by'
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'meal_allowance' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'utility_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'custom_allowances' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where(function($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            });
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where(function($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            });
    }

    // Accessors
    public function getTotalEarningsAttribute()
    {
        $customTotal = 0;
        if ($this->custom_allowances) {
            foreach ($this->custom_allowances as $allowance) {
                $customTotal += $allowance['amount'] ?? 0;
            }
        }

        return $this->basic_salary + $this->housing_allowance + $this->transport_allowance +
               $this->meal_allowance + $this->medical_allowance + $this->utility_allowance +
               $this->other_allowances + $customTotal;
    }

    public function getFormattedTotalEarningsAttribute()
    {
        return '₦' . number_format($this->total_earnings, 2);
    }

    public function getFormattedBasicSalaryAttribute()
    {
        return '₦' . number_format($this->basic_salary, 2);
    }

    public function getEffectivePeriodAttribute()
    {
        $from = $this->effective_from ? $this->effective_from->format('d M Y') : 'N/A';
        $to = $this->effective_to ? $this->effective_to->format('d M Y') : 'Present';
        return $from . ' - ' . $to;
    }

    // Methods
    public function deactivate()
    {
        $this->update([
            'is_active' => false,
            'effective_to' => now(),
        ]);
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }
}
