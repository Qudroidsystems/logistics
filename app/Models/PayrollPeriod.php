<?php
// app/Models/PayrollPeriod.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $table = 'payroll_periods';

    protected $fillable = [
        'period_name', 'month', 'year', 'start_date', 'end_date',
        'payment_date', 'status', 'total_gross_pay', 'total_employee_pension',
        'total_employer_pension', 'total_tax', 'total_nhf', 'total_loan_deductions',
        'total_other_deductions', 'total_net_pay', 'processed_by', 'processed_at',
        'approved_by', 'approved_at', 'journal_entry_id',
        'locked_by', 'locked_at', 'total_employer_cost', 'staff_count', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
        'total_gross_pay' => 'decimal:2',
        'total_employee_pension' => 'decimal:2',
        'total_employer_pension' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_nhf' => 'decimal:2',
        'total_loan_deductions' => 'decimal:2',
        'total_other_deductions' => 'decimal:2',
        'total_net_pay' => 'decimal:2',
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
        'locked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function payrollRuns()
    {
        return $this->hasMany(PayrollRun::class, 'payroll_period_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    // Accessors
    public function getFormattedTotalGrossAttribute()
    {
        return '₦' . number_format($this->total_gross_pay, 2);
    }

    public function getFormattedTotalNetAttribute()
    {
        return '₦' . number_format($this->total_net_pay, 2);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'secondary',
            'processing' => 'info',
            'approved' => 'primary',
            'paid' => 'success',
            'locked' => 'dark',
        ];
        $color = $badges[$this->status] ?? 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($this->status) . "</span>";
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->status === 'draft') return 0;
        if ($this->status === 'processing') return 25;
        if ($this->status === 'approved') return 50;
        if ($this->status === 'paid') return 75;
        if ($this->status === 'locked') return 100;
        return 0;
    }
}
