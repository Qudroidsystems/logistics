<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseVoucher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'voucher_no', 'expense_date', 'expense_category_id', 'vendor_id', 'staff_id', 'payee_name', 'description', 'amount',
        'payment_method', 'paid_from', 'reference', 'receipt_path', 'capitalise', 'asset_account', 'asset_life_months',
        'status', 'needs_second_approval', 'requested_by', 'approved_by', 'approved_at', 'second_approved_by', 'second_approved_at',
        'paid_by', 'paid_at', 'rejection_reason', 'purchase_request_id', 'fixed_asset_id',
    ];

    protected $casts = [
        'expense_date' => 'date', 'amount' => 'float', 'capitalise' => 'boolean', 'needs_second_approval' => 'boolean',
        'approved_at' => 'datetime', 'second_approved_at' => 'datetime', 'paid_at' => 'datetime',
    ];

    public const METHODS = ['bank_transfer' => 'Bank transfer', 'cash' => 'Cash / petty cash', 'cheque' => 'Cheque', 'pos' => 'POS / card', 'paystack' => 'Paystack transfer (staff)'];

    public const STATUS = [
        'draft' => ['Draft', 'st-muted'], 'submitted' => ['Awaiting approval', 'st-pending'], 'approved' => ['Approved — to pay', 'st-info'],
        'paying' => ['Being paid', 'st-pending'], 'paid' => ['Paid', 'st-paid'], 'rejected' => ['Declined', 'st-danger'], 'cancelled' => ['Cancelled', 'st-muted'],
    ];

    public function category() { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function secondApprover() { return $this->belongsTo(User::class, 'second_approved_by'); }
    public function payer() { return $this->belongsTo(User::class, 'paid_by'); }
    public function purchaseRequest() { return $this->belongsTo(PurchaseRequest::class); }
    public function asset() { return $this->belongsTo(FixedAsset::class, 'fixed_asset_id'); }
    public function label(): array { return self::STATUS[$this->status] ?? [ucfirst($this->status), 'st-muted']; }

    /** Approval is complete (one or two approvers as required). */
    public function fullyApproved(): bool
    {
        return $this->approved_by && (!$this->needs_second_approval || $this->second_approved_by);
    }
}
