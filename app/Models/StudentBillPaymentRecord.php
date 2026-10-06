<?php
// app/Models/StudentBillPaymentRecord.php (ENHANCED)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentBillPaymentRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'student_bill_payment_record';

    protected $fillable = [
        'student_bill_payment_id', 'class_id', 'termid_id', 'session_id',
        'amount_paid', 'last_payment', 'amount_owed', 'total_bill',
        'complete_payment', 'generated_by', 'invoiceNo', 'is_reversal',
        'reversal_reason', 'transaction_reference', 'payment_channel',
        'bank_name', 'cheque_number', 'notes'
    ];

    protected $casts = [
        'student_bill_payment_id' => 'integer',
        'class_id' => 'integer',
        'termid_id' => 'integer',
        'session_id' => 'integer',
        'amount_paid' => 'decimal:2',
        'last_payment' => 'decimal:2',
        'amount_owed' => 'decimal:2',
        'total_bill' => 'decimal:2',
        'complete_payment' => 'boolean',
        'is_reversal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function studentBillPayment()
    {
        return $this->belongsTo(StudentBillPayment::class, 'student_bill_payment_id');
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

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('is_reversal', false);
    }

    public function scopeReversals($query)
    {
        return $query->where('is_reversal', true);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Accessors
    public function getIsSuccessfulAttribute()
    {
        return !$this->is_reversal && $this->amount_paid > 0;
    }

    public function getFormattedAmountAttribute()
    {
        return '₦' . number_format($this->amount_paid, 2);
    }

    // Methods
    public function reverse($reason)
    {
        if ($this->is_reversal) {
            throw new \Exception('Cannot reverse a reversal record');
        }

        $this->update([
            'is_reversal' => true,
            'reversal_reason' => $reason,
        ]);

        // Update parent payment
        $payment = $this->studentBillPayment;
        if ($payment) {
            $newTotalPaid = $payment->total_paid - $this->amount_paid;
            $newBalance = $payment->total_balance + $this->amount_paid;

            $payment->update([
                'total_paid' => $newTotalPaid,
                'total_balance' => $newBalance,
                'payment_status' => $newBalance <= 0 ? 'completed' : ($newTotalPaid > 0 ? 'partial' : 'pending'),
            ]);
        }

        return $this;
    }
}
