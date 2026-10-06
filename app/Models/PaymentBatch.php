<?php
// app/Models/PaymentBatch.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentBatch extends Model
{
    use HasFactory;

    protected $table = 'payment_batches';

    protected $fillable = [
        'batch_no', 'student_id', 'payment_date', 'total_amount',
        'payment_method', 'reference_no', 'status', 'notes', 'receipt_data',
        'created_by', 'reversed_by', 'reversed_at', 'reversal_reason'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_date' => 'date',
        'reversed_at' => 'datetime',
        'receipt_data' => 'array',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function items()
    {
        return $this->hasMany(PaymentBatchItem::class);
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
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Generate receipt number
    public static function generateBatchNumber()
    {
        $year = date('Y');
        $month = date('m');
        $lastBatch = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastBatch ? intval(substr($lastBatch->batch_no, -4)) + 1 : 1;

        return 'BCH-' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
