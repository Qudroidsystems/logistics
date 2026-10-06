<?php
// app/Models/StaffPayment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff_payments';

    protected $fillable = [
        'staff_id', 'payroll_run_id', 'payment_reference', 'payment_type',
        'amount', 'payment_date', 'payment_method', 'bank_name',
        'account_number', 'account_name', 'transaction_ref', 'purpose',
        'attachment', 'payment_status', 'notes', 'created_by',
        'reversed_by', 'reversed_at', 'reversal_reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'reversed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return '₦' . number_format($this->amount, 2);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'processed' => 'info',
            'paid' => 'success',
            'failed' => 'danger',
            'reversed' => 'secondary',
        ];
        $color = $badges[$this->payment_status] ?? 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($this->payment_status) . "</span>";
    }
}
