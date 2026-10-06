<?php

namespace App\Services\Finance;

use App\Models\ExpenseVoucher;
use App\Models\FixedAsset;
use App\Models\PurchaseRequest;
use App\Support\FinanceSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Expense vouchers: draft → submitted → approved (a second approver above
 * the threshold) → paid. Nobody approves their own voucher. Paying posts to
 * the ledger and, for equipment, adds it to the asset register.
 */
class ExpenseService
{
    public static function available(): bool
    {
        return Schema::hasTable('expense_vouchers');
    }

    public function settings(): array
    {
        return FinanceSettings::get('expenses');
    }

    public function nextNumber(): string
    {
        $prefix = 'PV-' . now()->format('ym') . '-';
        $last = ExpenseVoucher::withTrashed()->where('voucher_no', 'like', $prefix . '%')->orderByDesc('id')->value('voucher_no');
        return $prefix . str_pad((string) ((int) ($last ? substr($last, -4) : 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    public function create(array $d, int $userId, bool $submit = true): ExpenseVoucher
    {
        $v = ExpenseVoucher::create($d + [
            'voucher_no' => $this->nextNumber(), 'requested_by' => $userId, 'status' => 'draft',
            'needs_second_approval' => (float) $d['amount'] > (float) $this->settings()['second_approval_above'],
        ]);
        if ($submit) $this->submit($v, $userId);
        return $v->fresh();
    }

    public function update(ExpenseVoucher $v, array $d): void
    {
        if (!in_array($v->status, ['draft', 'rejected'], true)) throw new \RuntimeException('Only a draft or declined voucher can be edited.');
        $v->update($d + ['needs_second_approval' => (float) ($d['amount'] ?? $v->amount) > (float) $this->settings()['second_approval_above'], 'status' => 'draft', 'rejection_reason' => null]);
    }

    public function submit(ExpenseVoucher $v, int $userId): void
    {
        if (!in_array($v->status, ['draft', 'rejected'], true)) throw new \RuntimeException('This voucher has already been submitted.');
        $S = $this->settings();
        if ((float) $v->amount > (float) $S['receipt_required_above'] && !$v->receipt_path && $v->payment_method !== 'paystack') {
            throw new \RuntimeException('Attach a receipt or invoice for amounts above ₦' . number_format($S['receipt_required_above']) . '.');
        }
        $check = app(BudgetService::class)->checkVoucher($v);
        if (!$check['ok'] && !empty($S['block_over_budget'])) throw new \RuntimeException($check['message']);
        $v->update(['status' => 'submitted', 'approved_by' => null, 'second_approved_by' => null, 'approved_at' => null, 'second_approved_at' => null]);
        $this->notify('Approve expenses', 'Expense waiting for approval', "{$v->voucher_no}: ₦" . number_format($v->amount, 2) . " — {$v->description}", route('finance.expenses.show', $v));
    }

    public function approve(ExpenseVoucher $v, int $userId): string
    {
        if ($v->status !== 'submitted') throw new \RuntimeException('Only a submitted voucher can be approved.');
        if ((int) $v->requested_by === $userId) throw new \RuntimeException('You cannot approve a voucher you raised.');
        if (!$v->approved_by) {
            $v->update(['approved_by' => $userId, 'approved_at' => now()]);
        } elseif ($v->needs_second_approval && !$v->second_approved_by) {
            if ((int) $v->approved_by === $userId) throw new \RuntimeException('A different person must give the second approval.');
            $v->update(['second_approved_by' => $userId, 'second_approved_at' => now()]);
        }
        if ($v->fresh()->fullyApproved()) {
            $v->update(['status' => 'approved']);
            $this->notify('Pay expenses', 'Expense ready to pay', "{$v->voucher_no}: ₦" . number_format($v->amount, 2) . ' approved.', route('finance.expenses.show', $v));
            return 'Approved — ready to pay.';
        }
        $this->notify('Approve expenses', 'Second approval needed', "{$v->voucher_no}: ₦" . number_format($v->amount, 2) . ' needs a second approver.', route('finance.expenses.show', $v));
        return 'First approval recorded. A second approver is needed for this amount.';
    }

    public function reject(ExpenseVoucher $v, int $userId, string $reason): void
    {
        if (!in_array($v->status, ['submitted', 'approved'], true)) throw new \RuntimeException('This voucher cannot be declined now.');
        $v->update(['status' => 'rejected', 'rejection_reason' => $reason, 'approved_by' => null, 'second_approved_by' => null]);
        $this->notifyUser((int) $v->requested_by, 'Expense declined', "{$v->voucher_no} was declined: {$reason}", route('finance.expenses.show', $v));
    }

    /** Record payment (cash / bank / cheque / POS) — or hand a staff reimbursement to Paystack payouts. */
    public function pay(ExpenseVoucher $v, int $userId, array $d = []): ?\App\Models\PayoutBatch
    {
        if ($v->status !== 'approved') throw new \RuntimeException('Approve the voucher before paying it.');
        if ((int) $v->requested_by === $userId && (int) $v->approved_by === $userId) throw new \RuntimeException('Separate duties: ask someone else to pay.');
        $method = $d['payment_method'] ?? $v->payment_method;

        if ($method === 'paystack') {
            if (!$v->staff_id) throw new \RuntimeException('Paystack payouts are for staff reimbursements. Choose the staff member on the voucher.');
            $batch = app(\App\Services\Payroll\PayoutService::class)->prepareSingle('expense', $v->id, (int) $v->staff_id, (float) $v->amount, $userId, 'Expense ' . $v->voucher_no);
            $v->update(['status' => 'paying', 'payment_method' => 'paystack', 'reference' => $batch->reference, 'paid_by' => $userId]);
            return $batch;
        }

        $v->update([
            'payment_method' => $method, 'reference' => $d['reference'] ?? $v->reference,
            'paid_from' => $d['paid_from'] ?? ($method === 'cash' ? FinanceSettings::get('accounting')['cash_account'] : FinanceSettings::get('accounting')['bank_account']),
            'paid_at' => !empty($d['paid_on']) ? \Carbon\Carbon::parse($d['paid_on']) : now(),
        ]);
        $this->completePayment($v->fresh(), $userId);
        return null;
    }

    /** From PayoutService once the Paystack transfer succeeds. */
    public function paidByTransfer(int $voucherId, string $reference): void
    {
        $v = ExpenseVoucher::find($voucherId);
        if (!$v || $v->status === 'paid') return;
        $v->update(['reference' => $reference, 'paid_from' => FinanceSettings::get('accounting')['bank_account'], 'paid_at' => now()]);
        $this->completePayment($v->fresh(), (int) $v->paid_by);
    }

    protected function completePayment(ExpenseVoucher $v, int $userId): void
    {
        DB::transaction(function () use ($v, $userId) {
            $v->update(['status' => 'paid', 'paid_by' => $userId ?: $v->paid_by, 'paid_at' => $v->paid_at ?? now()]);
            if ($v->capitalise && !$v->fixed_asset_id && Schema::hasTable('fixed_assets')) {
                $code = $v->asset_account ?: '1102';
                $asset = FixedAsset::create([
                    'asset_tag' => app(AssetService::class)->nextTag($code), 'name' => Str::limit($v->description, 140, ''), 'account_code' => $code,
                    'vendor_id' => $v->vendor_id, 'acquisition_date' => $v->expense_date, 'cost' => $v->amount,
                    'useful_life_months' => $v->asset_life_months ?: (FixedAsset::LIVES[$code] ?? 60), 'expense_voucher_id' => $v->id, 'created_by' => $userId,
                ]);
                $v->update(['fixed_asset_id' => $asset->id]);
            }
            if ($v->purchase_request_id) PurchaseRequest::where('id', $v->purchase_request_id)->whereIn('status', ['approved'])->update(['status' => 'ordered']);
        });
        if (class_exists(\App\Services\Accounting\LedgerPoster::class)) {
            try { app(\App\Services\Accounting\LedgerPoster::class)->expensePaid($v->fresh()); } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Expense not posted to ledger', ['voucher' => $v->id, 'error' => $e->getMessage()]);
            }
        }
        $this->notifyUser((int) $v->requested_by, 'Expense paid', "{$v->voucher_no} (₦" . number_format($v->amount, 2) . ') has been paid.', route('finance.expenses.show', $v));
    }

    public function cancel(ExpenseVoucher $v): void
    {
        if (in_array($v->status, ['paid', 'paying'], true)) throw new \RuntimeException('A paid voucher cannot be cancelled — reverse it in the journal instead.');
        $v->update(['status' => 'cancelled']);
    }

    protected function notify(string $permission, string $title, string $body, string $url): void
    {
        if (!class_exists(\App\Services\Messaging\PortalNotifier::class)) return;
        try {
            $ids = \App\Models\User::permission($permission)->pluck('id')->all();
            if ($ids) \App\Services\Messaging\PortalNotifier::toUsers($ids, $title, $body, $url, 'system');
        } catch (\Throwable $e) {}
    }

    protected function notifyUser(int $userId, string $title, string $body, string $url): void
    {
        if (!$userId || !class_exists(\App\Services\Messaging\PortalNotifier::class)) return;
        try { \App\Services\Messaging\PortalNotifier::toUsers([$userId], $title, $body, $url, 'system'); } catch (\Throwable $e) {}
    }
}
