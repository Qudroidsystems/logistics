<?php

namespace App\Services\Payroll;

use App\Models\PayoutBatch;
use App\Models\PayoutItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\StaffPayProfile;
use App\Services\Payment\PaystackTransfers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Pays staff: prepares a batch of transfers from an approved payroll month
 * (or one-off: loan disbursement, expense refund), releases it through
 * Paystack Transfers or a manual bank upload, and tracks each transfer.
 *
 * Two people are involved: one prepares, a different person releases.
 */
class PayoutService
{
    public const REF_PREFIX = 'CSKP-';

    public static function available(): bool
    {
        return Schema::hasTable('payout_batches') && Schema::hasTable('payout_items');
    }

    protected function gateway(): PaystackTransfers
    {
        return app(PaystackTransfers::class);
    }

    protected function newRef(int $batchId): string
    {
        return self::REF_PREFIX . $batchId . '-' . strtolower(Str::random(10));
    }

    /** Runs already covered by a live (not failed / cancelled) payout. */
    protected function coveredRunIds(int $periodId): array
    {
        return PayoutItem::whereIn('payout_batch_id', PayoutBatch::where('payroll_period_id', $periodId)->where('status', '!=', 'cancelled')->pluck('id'))
            ->whereNotIn('status', ['failed', 'reversed', 'skipped'])->whereNotNull('payroll_run_id')
            ->pluck('payroll_run_id')->all();
    }

    /** What's ready to pay for a month, and who can't be paid yet (and why). */
    public function readiness(PayrollPeriod $period, string $provider = 'paystack'): array
    {
        $covered = $this->coveredRunIds($period->id);
        $runs = PayrollRun::where('payroll_period_id', $period->id)->get();
        $profiles = StaffPayProfile::whereIn('staff_id', $runs->pluck('staff_id'))->get()->keyBy('staff_id');
        $names = DB::table('staffbioinfo as s')->leftJoin('users as u', 'u.id', '=', 's.userid')->whereIn('s.id', $runs->pluck('staff_id'))->pluck('u.name', 's.id');

        $ready = []; $blocked = []; $paid = 0;
        foreach ($runs as $run) {
            $name = $names[$run->staff_id] ?? ('Staff #' . $run->staff_id);
            if ($run->payment_status === 'paid' || in_array($run->id, $covered, true)) { $paid++; continue; }
            $p = $profiles[$run->staff_id] ?? null;
            $why = null;
            if ((float) $run->net_pay <= 0) $why = 'Nothing to pay';
            elseif (!$p || !$p->account_last4) $why = 'No bank account';
            elseif ($p->pay_status === 'hold') $why = 'Pay on hold' . ($p->hold_reason ? ": {$p->hold_reason}" : '');
            elseif ($provider === 'paystack' && (!$p->account_verified_at || !$p->bank_code)) $why = 'Bank account not verified';
            $row = ['run' => $run, 'profile' => $p, 'name' => $name];
            if ($why) { $row['why'] = $why; $blocked[] = $row; } else { $ready[] = $row; }
        }
        return ['ready' => $ready, 'blocked' => $blocked, 'already' => $paid,
                'total' => round(array_sum(array_map(fn ($r) => (float) $r['run']->net_pay, $ready)), 2)];
    }

    /** Prepare a salary batch for a month. */
    public function prepareSalary(PayrollPeriod $period, int $userId, string $provider = 'paystack', ?array $onlyRunIds = null): PayoutBatch
    {
        if (!in_array($period->status, ['approved', 'paid', 'locked'], true)) {
            throw new \RuntimeException('Approve the payroll before paying staff.');
        }
        $r = $this->readiness($period, $provider);
        $ready = $onlyRunIds ? array_values(array_filter($r['ready'], fn ($x) => in_array($x['run']->id, $onlyRunIds))) : $r['ready'];
        if (!$ready) throw new \RuntimeException('No one is ready to be paid for this month.');

        return DB::transaction(function () use ($period, $userId, $provider, $ready) {
            $batch = PayoutBatch::create([
                'payroll_period_id' => $period->id, 'reference' => 'PAY-' . now()->format('ymd') . '-' . strtoupper(Str::random(5)),
                'provider' => $provider, 'mode' => $this->gateway()->mode() === 'live' ? 'live' : 'test',
                'status' => 'draft', 'prepared_by' => $userId, 'note' => 'Salary · ' . $period->period_name,
            ]);
            foreach ($ready as $x) {
                PayoutItem::create([
                    'payout_batch_id' => $batch->id, 'payroll_run_id' => $x['run']->id, 'staff_id' => $x['run']->staff_id,
                    'purpose' => 'salary', 'amount' => round((float) $x['run']->net_pay, 2),
                    'bank_name' => $x['profile']->bank_name, 'account_last4' => $x['profile']->account_last4,
                    'account_name' => $x['profile']->account_name, 'recipient_code' => $x['profile']->paystack_recipient_code,
                    'reference' => $this->newRef($batch->id), 'status' => 'pending',
                ]);
            }
            $batch->refreshTotals();
            return $batch->fresh();
        });
    }

    /** One-off payout (loan disbursement, expense reimbursement…). */
    public function prepareSingle(string $purpose, int $sourceId, int $staffId, float $amount, int $userId, string $note, string $provider = 'paystack'): PayoutBatch
    {
        $p = StaffPayProfile::where('staff_id', $staffId)->first();
        if (!$p || !$p->account_last4) throw new \RuntimeException('This staff member has no bank account on their pay profile.');
        if ($provider === 'paystack' && !$p->account_verified_at) throw new \RuntimeException('Verify the staff member\'s bank account first.');

        return DB::transaction(function () use ($purpose, $sourceId, $staffId, $amount, $userId, $note, $provider, $p) {
            $batch = PayoutBatch::create([
                'reference' => strtoupper(substr($purpose, 0, 3)) . '-' . now()->format('ymd') . '-' . strtoupper(Str::random(5)),
                'provider' => $provider, 'mode' => $this->gateway()->mode() === 'live' ? 'live' : 'test',
                'status' => 'draft', 'prepared_by' => $userId, 'note' => mb_substr($note, 0, 250),
            ]);
            PayoutItem::create([
                'payout_batch_id' => $batch->id, 'staff_id' => $staffId, 'purpose' => $purpose, 'source_id' => $sourceId,
                'amount' => round($amount, 2), 'bank_name' => $p->bank_name, 'account_last4' => $p->account_last4,
                'account_name' => $p->account_name, 'recipient_code' => $p->paystack_recipient_code,
                'reference' => $this->newRef($batch->id), 'status' => 'pending',
            ]);
            $batch->refreshTotals();
            return $batch->fresh();
        });
    }

    /** Release a prepared batch (the money moves here). */
    public function release(PayoutBatch $batch, int $userId): array
    {
        if ($batch->status !== 'draft') throw new \RuntimeException('This batch has already been released.');
        if ((int) $batch->prepared_by === $userId && $this->needsSecondPerson()) {
            throw new \RuntimeException('The person who prepared this batch cannot release it. Ask another authorised user.');
        }
        $items = $batch->items()->where('status', 'pending')->get();
        if ($items->isEmpty()) throw new \RuntimeException('Nothing to send in this batch.');

        if ($batch->provider === 'manual') {
            $batch->update(['status' => 'processing', 'released_by' => $userId, 'released_at' => now()]);
            $batch->items()->where('status', 'pending')->update(['status' => 'queued']);
            $batch->refreshTotals();
            return ['sent' => $items->count(), 'failed' => 0, 'message' => 'Download the bank schedule, upload it to your bank, then mark the transfers as paid.'];
        }

        $gw = $this->gateway();
        if (!$gw->isReady()) throw new \RuntimeException('Paystack is not set up or is switched off (Finance › Payment Gateways).');
        $balance = $gw->balance();
        $total = round($items->sum('amount'), 2);
        if ($balance !== null && $balance < $total) {
            throw new \RuntimeException('Paystack balance (₦' . number_format($balance, 2) . ') is less than this batch (₦' . number_format($total, 2) . '). Fund the balance first.');
        }

        $batch->update(['status' => 'processing', 'released_by' => $userId, 'released_at' => now()]);
        $sent = 0; $failed = 0; $otp = 0;

        // 1) Make sure every payee is registered with Paystack.
        $ready = [];
        foreach ($items as $item) {
            $code = $this->recipientFor($item);
            if (!$code['ok']) { $this->markFailed($item, $code['message']); $failed++; continue; }
            $ready[] = $item;
        }

        // 2) Send in chunks of 100 (Paystack's bulk limit).
        foreach (array_chunk($ready, 100) as $chunk) {
            $res = $gw->bulk(array_map(fn (PayoutItem $i) => [
                'amount_kobo' => (int) round($i->amount * 100), 'recipient' => $i->recipient_code,
                'reference' => $i->reference, 'reason' => $this->reason($i, $batch),
            ], $chunk));
            foreach ($chunk as $i) {
                $i->increment('attempts');
                if (!$res['ok']) { $this->markFailed($i, $res['message']); $failed++; continue; }
                $r = $res['results'][$i->reference] ?? null;
                if (!$r) { $i->update(['status' => 'queued']); $sent++; continue; }
                $this->applyStatus($i, $r['status'], $r['transfer_code'] ?? null);
                $i->status === 'otp' ? $otp++ : ($i->status === 'failed' ? $failed++ : $sent++);
            }
        }

        $batch->refreshTotals();
        $msg = "{$sent} transfer(s) sent" . ($failed ? ", {$failed} failed" : '') . '.';
        if ($otp) $msg .= " {$otp} need an OTP — turn off “Confirm transfers with OTP” in your Paystack dashboard for bulk salary payments, or enter the OTP on each item.";
        return ['sent' => $sent, 'failed' => $failed, 'otp' => $otp, 'message' => $msg];
    }

    protected function needsSecondPerson(): bool
    {
        try {
            $rates = StatutoryRates::on(now()->toDateString());
            return !empty($rates['limits']['require_different_approver']);
        } catch (\Throwable $e) {
            return true;
        }
    }

    protected function reason(PayoutItem $i, PayoutBatch $b): string
    {
        return match ($i->purpose) {
            'salary' => 'Salary ' . ($b->period->period_name ?? ''),
            'loan'   => 'Staff loan disbursement',
            'expense'=> 'Expense reimbursement',
            default  => 'Payment from school',
        };
    }

    protected function recipientFor(PayoutItem $item): array
    {
        if ($item->recipient_code) return ['ok' => true, 'code' => $item->recipient_code];
        $p = StaffPayProfile::where('staff_id', $item->staff_id)->first();
        $acct = $p?->accountNumber();
        if (!$p || !$acct || !$p->bank_code) return ['ok' => false, 'message' => 'Bank details incomplete'];
        if ($p->paystack_recipient_code) {
            $item->update(['recipient_code' => $p->paystack_recipient_code]);
            return ['ok' => true, 'code' => $p->paystack_recipient_code];
        }
        $r = $this->gateway()->createRecipient($p->account_name ?: 'Staff ' . $item->staff_id, $acct, $p->bank_code);
        if (!$r['ok']) return $r;
        $p->forceFill(['paystack_recipient_code' => $r['code']])->save();
        $item->update(['recipient_code' => $r['code']]);
        return $r;
    }

    /** Map a Paystack transfer status onto an item. */
    protected function applyStatus(PayoutItem $item, string $status, ?string $transferCode = null, ?string $reason = null): void
    {
        $status = strtolower($status);
        $data = array_filter(['transfer_code' => $transferCode]);
        if ($status === 'success') {
            $item->update($data);
            $this->markPaid($item->fresh());
        } elseif ($status === 'otp') {
            $item->update($data + ['status' => 'otp']);
        } elseif (in_array($status, ['failed', 'abandoned', 'rejected', 'blocked'], true)) {
            $item->update($data);
            $this->markFailed($item, $reason ?: 'Transfer ' . $status);
        } elseif ($status === 'reversed') {
            $item->update($data);
            $this->markFailed($item, $reason ?: 'Transfer reversed by the bank', 'reversed');
        } else { // pending, processing, received, queued
            $item->update($data + ['status' => 'queued']);
        }
    }

    public function markPaid(PayoutItem $item, ?string $reference = null, string $status = 'success'): void
    {
        if ($item->isPaid()) return;
        DB::transaction(function () use ($item, $reference, $status) {
            $item->update(['status' => $status, 'paid_at' => now(), 'failure_reason' => null,
                           'transfer_code' => $reference ? mb_substr($reference, 0, 60) : $item->transfer_code]);
            if ($item->payroll_run_id) {
                PayrollRun::where('id', $item->payroll_run_id)->update([
                    'payment_status' => 'paid', 'paid_at' => now(), 'transaction_reference' => $reference ?: $item->reference,
                ]);
            }
        });
        $this->afterPaid($item->fresh());
    }

    public function markFailed(PayoutItem $item, string $reason, string $status = 'failed'): void
    {
        $item->update(['status' => $status, 'failure_reason' => mb_substr($reason, 0, 250)]);
        $this->sourceFailed($item, $reason);
    }

    /** A loan / expense payout did not go through: put the loan or voucher back so it can be paid again. */
    protected function sourceFailed(PayoutItem $item, string $reason): void
    {
        try {
            if ($item->purpose === 'loan' && class_exists(\App\Services\Loans\LoanService::class)) {
                app(\App\Services\Loans\LoanService::class)->disbursementFailed((int) $item->source_id, $reason);
            }
            if ($item->purpose === 'expense' && Schema::hasTable('expense_vouchers')) {
                \App\Models\ExpenseVoucher::where('id', $item->source_id)->where('status', 'paying')->update(['status' => 'approved']);
            }
        } catch (\Throwable $e) {}
    }

    /** Side effects once money has reached the staff member. */
    protected function afterPaid(PayoutItem $item): void
    {
        try {
            if ($item->purpose === 'loan' && class_exists(\App\Services\Loans\LoanService::class)) {
                app(\App\Services\Loans\LoanService::class)->disbursed((int) $item->source_id, $item->reference);
            }
            if ($item->purpose === 'expense' && class_exists(\App\Services\Finance\ExpenseService::class)) {
                app(\App\Services\Finance\ExpenseService::class)->paidByTransfer((int) $item->source_id, $item->reference);
            }
            if ($item->purpose === 'salary' && class_exists(\App\Services\Accounting\LedgerPoster::class)) {
                app(\App\Services\Accounting\LedgerPoster::class)->salaryPaid($item);
            }
        } catch (\Throwable $e) {
            Log::warning('Payout follow-up failed', ['item' => $item->id, 'error' => $e->getMessage()]);
        }

        // Settle the month once everyone is paid.
        if ($item->payroll_run_id && ($period = $item->run?->payrollPeriod)) {
            $unpaid = PayrollRun::where('payroll_period_id', $period->id)->where('net_pay', '>', 0)->where('payment_status', '!=', 'paid')->count();
            if ($unpaid === 0 && $period->status === 'approved') $period->update(['status' => 'paid']);
        }

        // Tell the staff member (in-portal bell).
        try {
            $userId = DB::table('staffbioinfo')->where('id', $item->staff_id)->value('userid');
            if ($userId && class_exists(\App\Services\Messaging\PortalNotifier::class)) {
                $what = $item->purpose === 'salary' ? 'Your salary' : ($item->purpose === 'loan' ? 'Your loan' : 'A payment');
                \App\Services\Messaging\PortalNotifier::toUsers([(int) $userId], 'Payment sent',
                    $what . ' of ₦' . number_format($item->amount, 2) . ' has been sent to your ' . ($item->bank_name ?: 'bank') . ' account ending ' . $item->account_last4 . '.',
                    route('my-pay.index'), 'system', 'payout-' . $item->id);
            }
        } catch (\Throwable $e) {}
    }

    /** Paystack webhook: transfer.success / transfer.failed / transfer.reversed. */
    public function handleEvent(string $event, array $data): bool
    {
        $ref = (string) ($data['reference'] ?? '');
        if (!str_starts_with($ref, self::REF_PREFIX)) return false;
        $item = PayoutItem::where('reference', $ref)->first();
        if (!$item) return true;
        $map = ['transfer.success' => 'success', 'transfer.failed' => 'failed', 'transfer.reversed' => 'reversed'];
        if (isset($map[$event])) {
            $this->applyStatus($item, $map[$event], $data['transfer_code'] ?? null, $data['reason'] ?? ($data['gateway_response'] ?? null));
            $item->batch?->refreshTotals();
        }
        return true;
    }

    /** Ask Paystack for the latest status of everything still in flight. */
    public function refresh(PayoutBatch $batch): int
    {
        $changed = 0;
        if ($batch->provider !== 'paystack') return 0;
        foreach ($batch->items()->whereIn('status', ['queued', 'otp'])->get() as $item) {
            $r = $this->gateway()->verifyTransfer($item->reference);
            if (!$r['ok']) continue;
            $before = $item->status;
            $this->applyStatus($item, $r['status'], $r['transfer_code'] ?? null, $r['reason'] ?? null);
            if ($item->fresh()->status !== $before) $changed++;
        }
        $batch->refreshTotals();
        return $changed;
    }

    public function finalizeOtp(PayoutItem $item, string $otp): array
    {
        if ($item->status !== 'otp' || !$item->transfer_code) return ['ok' => false, 'message' => 'This transfer is not waiting for an OTP.'];
        $r = $this->gateway()->finalize($item->transfer_code, $otp);
        if ($r['ok']) { $this->applyStatus($item, $r['status'] ?: 'pending'); $item->batch->refreshTotals(); }
        return $r;
    }

    /** Manual bank upload: record that selected items were paid. */
    public function markManual(PayoutBatch $batch, array $itemIds, string $reference, int $userId): int
    {
        $n = 0;
        foreach ($batch->items()->whereIn('id', $itemIds)->whereIn('status', ['queued', 'pending', 'failed', 'otp'])->get() as $item) {
            $this->markPaid($item, $reference, 'manual');
            $n++;
        }
        $batch->refreshTotals();
        return $n;
    }

    /** Put failed transfers into a fresh batch (new references) for another try. */
    public function retryFailed(PayoutBatch $batch, int $userId): PayoutBatch
    {
        $failed = $batch->items()->whereIn('status', ['failed', 'reversed'])->get();
        if ($failed->isEmpty()) throw new \RuntimeException('There are no failed transfers to retry.');
        return DB::transaction(function () use ($batch, $failed, $userId) {
            $new = PayoutBatch::create([
                'payroll_period_id' => $batch->payroll_period_id, 'reference' => $batch->reference . '-R' . strtoupper(Str::random(3)),
                'provider' => $batch->provider, 'mode' => $batch->mode, 'status' => 'draft', 'prepared_by' => $userId,
                'note' => 'Retry of ' . $batch->reference,
            ]);
            foreach ($failed as $f) {
                $p = StaffPayProfile::where('staff_id', $f->staff_id)->first();
                PayoutItem::create([
                    'payout_batch_id' => $new->id, 'payroll_run_id' => $f->payroll_run_id, 'staff_id' => $f->staff_id,
                    'purpose' => $f->purpose, 'source_id' => $f->source_id, 'amount' => $f->amount,
                    'bank_name' => $p->bank_name ?? $f->bank_name, 'account_last4' => $p->account_last4 ?? $f->account_last4,
                    'account_name' => $p->account_name ?? $f->account_name, 'recipient_code' => $p->paystack_recipient_code ?? null,
                    'reference' => $this->newRef($new->id), 'status' => 'pending',
                ]);
                $f->update(['status' => 'skipped', 'failure_reason' => trim(($f->failure_reason ?? '') . ' · moved to ' . $new->reference)]);
            }
            $new->refreshTotals();
            $batch->refreshTotals();
            return $new->fresh();
        });
    }

    public function cancel(PayoutBatch $batch): void
    {
        if ($batch->status !== 'draft') throw new \RuntimeException('Only a batch that has not been released can be cancelled.');
        $batch->update(['status' => 'cancelled']);
        $batch->items()->update(['status' => 'skipped', 'failure_reason' => 'Batch cancelled']);
        foreach ($batch->items()->whereIn('purpose', ['loan', 'expense'])->get() as $i) $this->sourceFailed($i, 'Payout cancelled');
    }
}
