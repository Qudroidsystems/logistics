<?php
// app/Models/StudentBillPayment.php (ENHANCED)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentBillPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'student_bill_payment';

    protected $fillable = [
        'student_id', 'school_bill_id', 'class_id', 'termid_id', 'session_id',
        'payment_method', 'status', 'payment_status', 'generated_by', 'delete_status',
        'total_paid', 'total_balance', 'last_payment_date', 'session_token'
    ];

    protected $casts = [
        'student_id' => 'integer',
        'school_bill_id' => 'integer',
        'class_id' => 'integer',
        'termid_id' => 'integer',
        'session_id' => 'integer',
        'total_paid' => 'decimal:2',
        'total_balance' => 'decimal:2',
        'last_payment_date' => 'datetime',
        'delete_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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

    public function paymentRecords()
    {
        return $this->hasMany(StudentBillPaymentRecord::class, 'student_bill_payment_id');
    }

    public function paymentBook()
    {
        return $this->hasOne(StudentBillPaymentBook::class, 'student_id', 'student_id')
            ->where('school_bill_id', $this->school_bill_id)
            ->where('class_id', $this->class_id)
            ->where('term_id', $this->termid_id)
            ->where('session_id', $this->session_id);
    }

    public function batchItems()
    {
        return $this->hasMany(PaymentBatchItem::class, 'student_bill_payment_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopePartial($query)
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopeOutstanding($query)
    {
        return $query->where('total_balance', '>', 0);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForTerm($query, $termId)
    {
        return $query->where('termid_id', $termId);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeInvoiceNotGenerated($query)
    {
        return $query->where('delete_status', '1');
    }

    public function scopeInvoiceGenerated($query)
    {
        return $query->where('delete_status', '0');
    }

    // Accessors
    public function getIsFullyPaidAttribute()
    {
        return $this->total_balance <= 0;
    }

    public function getPaymentProgressAttribute()
    {
        $total = $this->total_paid + $this->total_balance;
        if ($total <= 0) return 0;
        return ($this->total_paid / $total) * 100;
    }

    // Methods
    public function addPayment($amount, $paymentMethod, $referenceNo = null)
    {
        if ($amount <= 0) {
            throw new \Exception('Payment amount must be greater than zero');
        }

        if ($amount > $this->total_balance) {
            throw new \Exception('Payment amount cannot exceed outstanding balance');
        }

        $newTotalPaid = $this->total_paid + $amount;
        $newBalance = $this->total_balance - $amount;
        $isComplete = $newBalance <= 0;

        $this->update([
            'total_paid' => $newTotalPaid,
            'total_balance' => $newBalance,
            'payment_status' => $isComplete ? 'completed' : 'partial',
            'last_payment_date' => now(),
            'payment_method' => $paymentMethod,
        ]);

        $record = $this->paymentRecords()->create([
            'class_id' => $this->class_id,
            'termid_id' => $this->termid_id,
            'session_id' => $this->session_id,
            'amount_paid' => $amount,
            'last_payment' => $amount,
            'amount_owed' => $newBalance,
            'total_bill' => $this->total_paid + $this->total_balance,
            'complete_payment' => $isComplete ? 1 : 0,
            'generated_by' => auth()->id(),
            'transaction_reference' => $referenceNo,
        ]);

        return $record;
    }


    // In StudentBillPayment model
public static function getTotalPaid(int $studentId, int $schoolBillId, int $termId, int $sessionId): float
{
    return (float) self::where('student_id', $studentId)
        ->where('school_bill_id', $schoolBillId)
        ->where('termid_id', $termId)
        ->where('session_id', $sessionId)
        ->value('total_paid') ?? 0.0;
}


    public function generateInvoice()
    {
        if ($this->delete_status === '0') {
            throw new \Exception('Invoice already generated for this payment');
        }

        $invoiceNo = $this->generateInvoiceNumber();

        $invoice = StudentBillInvoice::create([
            'invoice_no' => $invoiceNo,
            'student_id' => $this->student_id,
            'school_bill_id' => $this->school_bill_id,
            'status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'class_id' => $this->class_id,
            'termid_id' => $this->termid_id,
            'session_id' => $this->session_id,
            'generated_by' => auth()->id(),
            'amount' => $this->total_paid,
        ]);

        $this->update(['delete_status' => '0']);

        // Update all payment records with invoice number
        $this->paymentRecords()->update(['invoiceNo' => $invoiceNo]);

        return $invoice;
    }

    private function generateInvoiceNumber()
    {
        $year = date('Y');
        $lastInvoice = StudentBillInvoice::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? intval(substr($lastInvoice->invoice_no, -5)) + 1 : 1;

        return 'INV-' . $year . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}
