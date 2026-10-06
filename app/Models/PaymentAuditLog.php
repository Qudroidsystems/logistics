<?php
// app/Models/PaymentAuditLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAuditLog extends Model
{
    protected $table = 'payment_audit_logs';

    protected $fillable = [
        'student_id', 'school_bill_id', 'student_bill_payment_id',
        'student_bill_payment_record_id', 'class_id', 'term_id', 'session_id',
        'action', 'entity_type', 'old_values', 'new_values', 'amount',
        'payment_method', 'performed_by', 'ip_address', 'notes',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'amount'     => 'decimal:2',
    ];

    public function performer()
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }

    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class, 'student_id');
    }
}