<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    protected $fillable = [
        'pr_no', 'title', 'department', 'requested_by', 'needed_by', 'items', 'estimated_total', 'expense_category_id', 'vendor_id',
        'status', 'approved_by', 'approved_at', 'rejection_reason', 'notes', 'expense_voucher_id', 'received_at',
    ];
    protected $casts = ['items' => 'array', 'estimated_total' => 'float', 'needed_by' => 'date', 'approved_at' => 'datetime', 'received_at' => 'datetime'];

    public const STATUS = [
        'submitted' => ['Awaiting approval', 'st-pending'], 'approved' => ['Approved', 'st-info'], 'rejected' => ['Declined', 'st-danger'],
        'ordered' => ['Ordered / paid', 'st-pending'], 'received' => ['Received', 'st-paid'], 'closed' => ['Closed', 'st-muted'],
    ];

    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function category() { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function voucher() { return $this->belongsTo(ExpenseVoucher::class, 'expense_voucher_id'); }
    public function label(): array { return self::STATUS[$this->status] ?? [ucfirst($this->status), 'st-muted']; }
}
