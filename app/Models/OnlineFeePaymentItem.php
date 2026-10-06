<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineFeePaymentItem extends Model
{
    protected $table = 'online_fee_payment_items';

    protected $fillable = [
        'online_fee_payment_id', 'school_bill_id', 'title', 'class_id', 'term_id', 'session_id',
        'is_arrear', 'payable_kobo', 'balance_kobo', 'amount_kobo', 'applied_kobo',
        'student_bill_payment_record_id',
    ];

    protected $casts = [
        'is_arrear'    => 'boolean',
        'payable_kobo' => 'integer',
        'balance_kobo' => 'integer',
        'amount_kobo'  => 'integer',
        'applied_kobo' => 'integer',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(OnlineFeePayment::class, 'online_fee_payment_id');
    }
}
