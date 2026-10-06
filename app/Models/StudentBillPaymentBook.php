<?php
// app/Models/StudentBillPaymentBook.php (ENHANCED)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentBillPaymentBook extends Model
{
    use HasFactory;

    protected $table = 'student_bill_payment_book';

    protected $fillable = [
        'student_id', 'school_bill_id', 'amount_paid', 'amount_owed',
        'payment_status', 'class_id', 'term_id', 'session_id', 'generated_by',
        'original_amount', 'scholarship_deduction', 'discount_deduction', 'adjusted_amount'
    ];

    protected $casts = [
        'student_id' => 'integer',
        'school_bill_id' => 'integer',
        'amount_paid' => 'decimal:2',
        'amount_owed' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'scholarship_deduction' => 'decimal:2',
        'discount_deduction' => 'decimal:2',
        'adjusted_amount' => 'decimal:2',
        'class_id' => 'integer',
        'term_id' => 'integer',
        'session_id' => 'integer',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
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
        return $this->belongsTo(Schoolterm::class, 'term_id');
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
    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopeOutstanding($query)
    {
        return $query->where('amount_owed', '>', 0);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForTerm($query, $termId)
    {
        return $query->where('term_id', $termId);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // Accessors
    public function getTotalSavingsAttribute()
    {
        return ($this->scholarship_deduction ?? 0) + ($this->discount_deduction ?? 0);
    }

    public function getIsFullyPaidAttribute()
    {
        return $this->amount_owed <= 0;
    }

    public function getPaymentPercentageAttribute()
    {
        $total = $this->amount_paid + $this->amount_owed;
        if ($total <= 0) return 0;
        return ($this->amount_paid / $total) * 100;
    }

    // Methods
    public static function updateOrCreateForStudent($studentId, $billId, $classId, $termId, $sessionId, $adjustmentData)
    {
        return self::updateOrCreate(
            [
                'student_id' => $studentId,
                'school_bill_id' => $billId,
                'class_id' => $classId,
                'term_id' => $termId,
                'session_id' => $sessionId,
            ],
            [
                'original_amount' => $adjustmentData['original_amount'],
                'scholarship_deduction' => $adjustmentData['scholarship_deduction'],
                'discount_deduction' => $adjustmentData['discount_deduction'],
                'adjusted_amount' => $adjustmentData['adjusted_amount'],
            ]
        );
    }

    public function recordPayment($amount)
    {
        if ($amount <= 0) {
            throw new \Exception('Payment amount must be greater than zero');
        }

        if ($amount > $this->amount_owed) {
            throw new \Exception('Payment amount cannot exceed outstanding balance');
        }

        $newAmountPaid = $this->amount_paid + $amount;
        $newAmountOwed = $this->amount_owed - $amount;
        $newStatus = $newAmountOwed <= 0 ? 'completed' : 'partial';

        $this->update([
            'amount_paid' => $newAmountPaid,
            'amount_owed' => $newAmountOwed,
            'payment_status' => $newStatus,
        ]);

        return $this;
    }

    public function getPaymentBreakdown()
    {
        return [
            'original_fees' => $this->original_amount,
            'scholarship_savings' => $this->scholarship_deduction,
            'discount_savings' => $this->discount_deduction,
            'total_savings' => $this->total_savings,
            'amount_to_pay' => $this->adjusted_amount,
            'amount_paid' => $this->amount_paid,
            'outstanding' => $this->amount_owed,
            'completion_percentage' => $this->payment_percentage,
        ];
    }
}
