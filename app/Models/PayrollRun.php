<?php
// app/Models/PayrollRun.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasFactory;

    protected $table = 'payroll_runs';

    protected $fillable = [
        'payroll_period_id', 'staff_id', 'salary_structure_id',
        'basic_salary', 'housing_allowance', 'transport_allowance',
        'meal_allowance', 'medical_allowance', 'utility_allowance',
        'other_allowances', 'custom_allowances', 'overtime_pay',
        'bonus', 'commission', 'total_earnings',
        'paye_tax', 'employee_pension', 'employer_pension',
        'nhf', 'nsitf', 'loan_repayment', 'advance_repayment',
        'union_dues', 'cooperative_deductions', 'other_deductions',
        'loan_details', 'advance_details', 'total_deductions',
        'net_pay', 'bank_name', 'account_number', 'account_name',
        'payment_status', 'paid_at', 'transaction_reference',
        'status', 'processed_by',
        'taxable_income', 'annual_chargeable', 'rent_relief', 'pension_base', 'nhia', 'employer_nhia', 'itf',
        'employer_cost', 'proration', 'tax_rule', 'tax_breakdown', 'tax_state', 'tin', 'pfa_name', 'rsa_pin',
        'warnings', 'verify_code',
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
        'overtime_pay' => 'decimal:2',
        'bonus' => 'decimal:2',
        'commission' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'paye_tax' => 'decimal:2',
        'employee_pension' => 'decimal:2',
        'employer_pension' => 'decimal:2',
        'nhf' => 'decimal:2',
        'nsitf' => 'decimal:2',
        'loan_repayment' => 'decimal:2',
        'advance_repayment' => 'decimal:2',
        'union_dues' => 'decimal:2',
        'cooperative_deductions' => 'decimal:2',
        'other_deductions' => 'array',
        'loan_details' => 'array',
        'advance_details' => 'array',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'paid_at' => 'datetime',
        'tax_breakdown' => 'array',
        'warnings' => 'array',
        'proration' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function lines()
    {
        return $this->hasMany(PayrollRunLine::class, 'payroll_run_id')->orderBy('sort');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function salaryStructure()
    {
        return $this->belongsTo(StaffSalaryStructure::class, 'salary_structure_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function payments()
    {
        return $this->hasMany(StaffPayment::class, 'payroll_run_id');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    // Accessors
    public function getFormattedNetPayAttribute()
    {
        return '₦' . number_format($this->net_pay, 2);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'secondary',
            'approved' => 'primary',
            'paid' => 'success',
        ];
        $color = $badges[$this->status] ?? 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($this->status) . "</span>";
    }

    public function getPaymentStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'processed' => 'info',
            'paid' => 'success',
            'failed' => 'danger',
        ];
        $color = $badges[$this->payment_status] ?? 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($this->payment_status) . "</span>";
    }
}
