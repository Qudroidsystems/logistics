<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineFeePayment extends Model
{
    protected $table = 'online_fee_payments';

    protected $fillable = [
        'reference', 'student_id', 'payer_user_id', 'payer_type', 'email',
        'class_id', 'term_id', 'session_id',
        'amount_kobo', 'arrears_kobo', 'current_kobo', 'currency', 'gateway', 'mode',
        'status', 'channel', 'gateway_fee_kobo', 'paid_kobo', 'applied_kobo', 'unapplied_kobo',
        'needs_review', 'access_code', 'authorization_url',
        'paid_at', 'posted_at', 'last_verified_at', 'failure_reason', 'gateway_response',
    ];

    protected $casts = [
        'amount_kobo'      => 'integer',
        'arrears_kobo'     => 'integer',
        'current_kobo'     => 'integer',
        'gateway_fee_kobo' => 'integer',
        'paid_kobo'        => 'integer',
        'applied_kobo'     => 'integer',
        'unapplied_kobo'   => 'integer',
        'needs_review'     => 'boolean',
        'gateway_response' => 'array',
        'paid_at'          => 'datetime',
        'posted_at'        => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    public const STATUS_LABELS = [
        'pending'         => 'Awaiting payment',
        'success'         => 'Paid',
        'failed'          => 'Failed',
        'abandoned'       => 'Not completed',
        'amount_mismatch' => 'Needs review',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OnlineFeePaymentItem::class, 'online_fee_payment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    public function naira(string $field = 'amount_kobo'): float
    {
        return round(((int) $this->{$field}) / 100, 2);
    }

    public function isFinal(): bool
    {
        return $this->posted_at !== null || in_array($this->status, ['failed', 'amount_mismatch'], true);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }
}
