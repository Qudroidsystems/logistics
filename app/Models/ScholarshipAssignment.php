<?php
// app/Models/ScholarshipAssignment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarshipAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'scholarship_assignments';

    protected $fillable = [
        'scholarship_id', 'student_id', 'application_no', 'status',
        'approved_at', 'renewed_at', 'effective_from', 'effective_to',
        'value_type', 'value', 'cap_amount', 'reason', 'rejection_reason',
        'revocation_reason', 'assigned_by', 'approved_by'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'cap_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
        'renewed_at' => 'datetime',
    ];

    // Relationships
    public function scholarship()
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
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

    // Check if assignment is active
    public function isActive()
    {
        return $this->status === 'active'
            && $this->effective_from <= now()
            && ($this->effective_to === null || $this->effective_to >= now());
    }

    // Renew scholarship
    public function renew($newEffectiveTo = null)
    {
        $this->update([
            'renewed_at' => now(),
            'effective_to' => $newEffectiveTo ?? now()->addYear(),
            'status' => 'active'
        ]);
    }

    // Revoke scholarship
    public function revoke($reason)
    {
        $this->update([
            'status' => 'revoked',
            'revocation_reason' => $reason
        ]);
    }
}
