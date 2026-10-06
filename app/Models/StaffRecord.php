<?php
// app/Models/StaffRecord.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff_records';

    protected $fillable = [
        'user_id',
        'staff_no',
        'employment_id',
        'department',
        'position',
        'job_title',
        'grade_level',
        'step',
        'date_of_employment',
        'date_of_confirmation',
        'qualification',
        'bank_name',
        'account_number',
        'account_name',
        'pension_id',
        'nhf_number',
        'tin_number',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_employment' => 'date',
        'date_of_confirmation' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salaryStructures()
    {
        return $this->hasMany(StaffSalaryStructure::class, 'staff_id');
    }

    public function activeSalaryStructure()
    {
        return $this->hasOne(StaffSalaryStructure::class, 'staff_id')
            ->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where(function($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            });
    }

    public function payrollRuns()
    {
        return $this->hasMany(PayrollRun::class, 'staff_id');
    }

    public function staffPayments()
    {
        return $this->hasMany(StaffPayment::class, 'staff_id');
    }

    public function loans()
    {
        return $this->hasMany(LoanAdvance::class, 'staff_id');
    }

    public function activeLoans()
    {
        return $this->hasMany(LoanAdvance::class, 'staff_id')->where('status', 'active');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->user ? $this->user->name : 'N/A';
    }

    public function getFormattedEmploymentDateAttribute()
    {
        return $this->date_of_employment ? $this->date_of_employment->format('d M, Y') : 'N/A';
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'active' => 'success',
            'inactive' => 'danger',
            'suspended' => 'warning',
            'retired' => 'secondary',
        ];
        $color = $badges[$this->status] ?? 'secondary';
        return "<span class='badge bg-{$color}'>{$this->status}</span>";
    }
}
