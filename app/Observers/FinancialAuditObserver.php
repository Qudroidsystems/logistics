<?php

namespace App\Observers;

use App\Models\FinancialAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Generic financial audit recorder. Attached to money-related models, it writes
 * an immutable before/after row to financial_audit_logs on create/update/delete.
 * Only records changes made while a real user is signed in, so seeders, imports
 * and background jobs never create audit noise.
 */
class FinancialAuditObserver
{
    /** Human labels per model short-name. */
    protected const LABELS = [
        'ExpenseVoucher' => 'Expense voucher', 'JournalEntry' => 'Journal entry',
        'LoanAdvance' => 'Staff loan/advance', 'PayoutBatch' => 'Salary payout batch',
        'PayoutItem' => 'Salary payout', 'StudentBillPayment' => 'Fee payment',
        'StudentBillPaymentRecord' => 'Fee payment record', 'OnlineFeePayment' => 'Online fee payment',
        'StatutoryRemittance' => 'Statutory remittance', 'DiscountAssignment' => 'Discount assignment',
        'ScholarshipAssignment' => 'Scholarship assignment', 'Budget' => 'Budget',
        'FixedAsset' => 'Fixed asset', 'PurchaseRequest' => 'Purchase request',
        'CoopTransaction' => 'Cooperative transaction', 'StaffPayment' => 'Staff payment',
        'SchoolBillModel' => 'School bill',
    ];

    protected const AMOUNT_KEYS = ['amount', 'total_amount', 'total', 'amount_paid', 'amount_due', 'bill_amount', 'net_pay', 'principal', 'value', 'monthly_repayment'];
    protected const REF_KEYS    = ['voucher_no', 'entry_no', 'reference_no', 'reference', 'batch_no', 'code', 'invoice_no'];
    protected const DATE_KEYS   = ['expense_date', 'entry_date', 'txn_date', 'payment_date', 'due_date', 'approval_date', 'date'];
    protected const IGNORE      = ['updated_at', 'created_at', 'remember_token', 'password'];

    public function created(Model $m): void { $this->record($m, 'created'); }
    public function updated(Model $m): void { $this->record($m, 'updated'); }
    public function deleted(Model $m): void { $this->record($m, 'deleted'); }

    protected function record(Model $m, string $event): void
    {
        try {
            if (!Auth::check()) return; // only user-driven changes

            $short = class_basename($m);
            $attrs = $m->getAttributes();

            $changes = [];
            if ($event === 'updated') {
                foreach ($m->getChanges() as $field => $new) {
                    if (in_array($field, self::IGNORE, true) || str_ends_with($field, '_token')) continue;
                    $changes[$field] = [$this->scalar($m->getOriginal($field)), $this->scalar($new)];
                }
                if (!$changes) return; // nothing meaningful changed
            }

            $u = Auth::user();
            FinancialAuditLog::create([
                'auditable_type' => $short,
                'auditable_id'   => $m->getKey(),
                'model_label'    => self::LABELS[$short] ?? $short,
                'event'          => $event,
                'user_id'        => $u?->id,
                'user_name'      => $u?->name,
                'ref'            => $this->pick($attrs, self::REF_KEYS) ?: ('#' . $m->getKey()),
                'amount'         => $this->amount($attrs),
                'changes'        => $changes ?: null,
                'summary'        => $this->summary($short, $event, $attrs),
                'business_date'  => $this->pick($attrs, self::DATE_KEYS),
                'ip'             => Request::ip(),
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            // auditing must never break a financial operation
        }
    }

    protected function pick(array $attrs, array $keys)
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $attrs) && $attrs[$k] !== null && $attrs[$k] !== '') return $attrs[$k];
        }
        return null;
    }

    protected function amount(array $attrs): ?float
    {
        $v = $this->pick($attrs, self::AMOUNT_KEYS);
        return is_numeric($v) ? (float) $v : null;
    }

    protected function scalar($v)
    {
        if (is_null($v) || is_scalar($v)) return $v;
        return json_encode($v);
    }

    protected function summary(string $short, string $event, array $attrs): string
    {
        $label = self::LABELS[$short] ?? $short;
        $ref = $this->pick($attrs, self::REF_KEYS);
        $verb = ['created' => 'created', 'updated' => 'edited', 'deleted' => 'deleted'][$event] ?? $event;
        return trim(ucfirst($verb) . ' ' . strtolower($label) . ($ref ? ' ' . $ref : ''));
    }
}
