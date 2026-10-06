<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutItem extends Model
{
    protected $fillable = [
        'payout_batch_id', 'payroll_run_id', 'staff_id', 'purpose', 'source_id', 'amount', 'bank_name', 'account_last4',
        'account_name', 'recipient_code', 'reference', 'transfer_code', 'status', 'failure_reason', 'attempts', 'paid_at',
    ];

    protected $casts = ['amount' => 'float', 'paid_at' => 'datetime'];

    public const STATUS = [
        'pending' => ['Waiting', 'st-muted'], 'queued' => ['Sent to bank', 'st-pending'], 'otp' => ['Needs OTP', 'st-warning'],
        'success' => ['Paid', 'st-paid'], 'manual' => ['Paid (manual)', 'st-paid'], 'failed' => ['Failed', 'st-danger'],
        'reversed' => ['Reversed', 'st-danger'], 'skipped' => ['Skipped', 'st-muted'],
    ];

    public const FINAL = ['success', 'manual', 'failed', 'reversed', 'skipped'];

    public function batch() { return $this->belongsTo(PayoutBatch::class, 'payout_batch_id'); }
    public function run() { return $this->belongsTo(PayrollRun::class, 'payroll_run_id'); }
    public function staff() { return $this->belongsTo(Staff::class, 'staff_id'); }

    public function label(): array { return self::STATUS[$this->status] ?? [ucfirst($this->status), 'st-muted']; }
    public function isPaid(): bool { return in_array($this->status, ['success', 'manual'], true); }
}
