<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "staffbioinfo";
    protected $primaryKey = "id";

    protected $fillable = [
        'userid',
        'title',
        'employmentid',
        'phonenumber',
        'email',
        'gender',
        'maritalstatus',
        'numberofchildren',
        'spousenumber',
        'address',
        'nationality',
        'state',
        'local',
        'religion',
        'dateofbirth',
        // Additional fields for payroll
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
    ];

    protected $casts = [
        'dateofbirth' => 'date',
        'date_of_employment' => 'date',
        'date_of_confirmation' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user associated with the Staff
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'id');
    }
    

    /**
     * Get formatted date of birth
     */
    public function getFormattedDateOfBirthAttribute(): ?string
    {
        if ($this->dateofbirth) {
            try {
                return date('F j, Y', strtotime($this->dateofbirth));
            } catch (\Exception $e) {
                return $this->dateofbirth;
            }
        }
        return null;
    }

    /**
     * Get formatted date of employment
     */
    public function getFormattedDateOfEmploymentAttribute(): ?string
    {
        if ($this->date_of_employment) {
            try {
                return date('F j, Y', strtotime($this->date_of_employment));
            } catch (\Exception $e) {
                return $this->date_of_employment;
            }
        }
        return null;
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
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

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->user ? $this->user->name : 'N/A';
    }

    /**
     * Get staff number (use employmentid as staff number if not set)
     */
    public function getStaffNumberAttribute(): string
    {
        return $this->employmentid ?? 'N/A';
    }

    /**
     * Get active salary structure
     */
    public function activeSalaryStructure()
    {
        return $this->hasOne(StaffSalaryStructure::class, 'staff_id')
            ->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where(function($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            });
    }

    /**
     * Get salary structures
     */
    public function salaryStructures()
    {
        return $this->hasMany(StaffSalaryStructure::class, 'staff_id');
    }

    /**
     * Get payroll runs
     */
    public function payrollRuns()
    {
        return $this->hasMany(PayrollRun::class, 'staff_id');
    }

    /**
     * Get staff payments
     */
    public function staffPayments()
    {
        return $this->hasMany(StaffPayment::class, 'staff_id');
    }

    /**
     * Scope for active staff
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for staff by department
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }
}
