<?php
// app/Models/PaymentBatchItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentBatchItem extends Model
{
    use HasFactory;

    protected $table = 'payment_batch_items';

    protected $fillable = [
        'payment_batch_id', 'school_bill_id', 'class_id', 'termid_id', 'session_id',
        'original_amount', 'scholarship_deduction', 'discount_deduction', 'adjusted_amount',
        'amount_paid', 'balance_before', 'balance_after', 'student_bill_payment_id'
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'scholarship_deduction' => 'decimal:2',
        'discount_deduction' => 'decimal:2',
        'adjusted_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // Relationships
    public function batch()
    {
        return $this->belongsTo(PaymentBatch::class, 'payment_batch_id');
    }

    public function schoolBill()
    {
        return $this->belongsTo(SchoolBillModel::class, 'school_bill_id');
    }

    public function class()
    {
        return $this->belongsTo(Schoolclass::class, 'class_id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid_id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }

    public function studentBillPayment()
    {
        return $this->belongsTo(StudentBillPayment::class, 'student_bill_payment_id');
    }
}
