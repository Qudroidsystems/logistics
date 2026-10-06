<?php

namespace App\Modules\Payments\Ledger;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * The only code allowed to write ledger rows or change ledger_accounts.balance.
 *
 * Rules enforced here (and again by database triggers):
 *  - every transaction balances: total debits = total credits
 *  - every posting carries an idempotency key; repeating it returns the original transaction
 *  - accounts are locked in id order, so concurrent postings cannot deadlock
 *  - accounts that may not go negative are checked before the entry is written
 *  - mistakes are corrected with reverse(), never by editing
 */
class LedgerPoster
{
    /**
     * @param  array<int, array{account_id:int, direction:'debit'|'credit', amount:int}>  $entries
     * @param  array{operator_id:int, kind:string, reference_type?:string, reference_id?:int, description?:string, posted_by?:int, is_test?:bool, reverses_id?:int}  $meta
     * @return array{id:int, created:bool}
     */
    public function post(string $idempotencyKey, array $meta, array $entries): array
    {
        $this->assertBalanced($entries);

        return DB::transaction(function () use ($idempotencyKey, $meta, $entries) {
            $existing = DB::table('ledger_transactions')->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return ['id' => (int) $existing->id, 'created' => false];
            }

            // Lock every touched account in a fixed order.
            $accountIds = collect($entries)->pluck('account_id')->unique()->sort()->values()->all();
            $accounts = DB::table('ledger_accounts')->whereIn('id', $accountIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if ($accounts->count() !== count($accountIds)) {
                throw new RuntimeException('One or more ledger accounts do not exist.');
            }

            // Net effect per account, using each account's normal side.
            $deltas = [];
            foreach ($entries as $e) {
                $account = $accounts[$e['account_id']];
                if ($account->closed_at !== null) {
                    throw new RuntimeException("Ledger account {$account->id} is closed.");
                }
                $sign = $this->increases($account->type, $e['direction']) ? 1 : -1;
                $deltas[$account->id] = ($deltas[$account->id] ?? 0) + $sign * $e['amount'];
            }
            foreach ($deltas as $id => $delta) {
                $account = $accounts[$id];
                if (! $account->allow_negative && $account->balance + $delta < 0) {
                    throw new InsufficientFunds("Account {$id} would go below zero.");
                }
            }

            $txId = DB::table('ledger_transactions')->insertGetId([
                'public_id' => (string) Str::ulid(),
                'operator_id' => $meta['operator_id'],
                'kind' => $meta['kind'],
                'reference_type' => $meta['reference_type'] ?? null,
                'reference_id' => $meta['reference_id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'description' => $meta['description'] ?? null,
                'reverses_id' => $meta['reverses_id'] ?? null,
                'posted_by' => $meta['posted_by'] ?? null,
                'is_test' => $meta['is_test'] ?? false,
                'posted_at' => now(),
            ]);

            $running = $accounts->map(fn ($a) => $a->balance)->all();
            $rows = [];
            foreach ($entries as $e) {
                $account = $accounts[$e['account_id']];
                $sign = $this->increases($account->type, $e['direction']) ? 1 : -1;
                $running[$account->id] += $sign * $e['amount'];
                $rows[] = [
                    'transaction_id' => $txId,
                    'account_id' => $account->id,
                    'direction' => $e['direction'],
                    'amount' => $e['amount'],
                    'balance_after' => $running[$account->id],
                    'created_at' => now(),
                ];
            }
            DB::table('ledger_entries')->insert($rows);

            foreach ($deltas as $id => $delta) {
                DB::table('ledger_accounts')->where('id', $id)->update([
                    'balance' => DB::raw('balance + ' . (int) $delta),
                    'balance_version' => DB::raw('balance_version + 1'),
                    'updated_at' => now(),
                ]);
            }

            return ['id' => $txId, 'created' => true];
        });
    }

    /** Post the mirror image of an earlier transaction. */
    public function reverse(int $transactionId, string $idempotencyKey, ?int $postedBy = null, ?string $reason = null): array
    {
        $tx = DB::table('ledger_transactions')->find($transactionId);
        if (! $tx) {
            throw new RuntimeException("Ledger transaction {$transactionId} not found.");
        }

        $entries = DB::table('ledger_entries')->where('transaction_id', $transactionId)->get()
            ->map(fn ($e) => [
                'account_id' => (int) $e->account_id,
                'direction' => $e->direction === 'debit' ? 'credit' : 'debit',
                'amount' => (int) $e->amount,
            ])->all();

        return $this->post($idempotencyKey, [
            'operator_id' => (int) $tx->operator_id,
            'kind' => 'adjustment',
            'reference_type' => 'ledger_transaction',
            'reference_id' => $transactionId,
            'reverses_id' => $transactionId,
            'description' => $reason ?? "Reversal of {$tx->public_id}",
            'posted_by' => $postedBy,
            'is_test' => (bool) $tx->is_test,
        ], $entries);
    }

    /**
     * Assets and expenses grow with debits; liabilities, revenue and equity grow with credits.
     * Customer, escrow and wallet accounts are liabilities of the platform.
     */
    private function increases(string $accountType, string $direction): bool
    {
        $debitNormal = in_array($accountType, ['asset', 'expense'], true);

        return $debitNormal ? $direction === 'debit' : $direction === 'credit';
    }

    private function assertBalanced(array $entries): void
    {
        if (count($entries) < 2) {
            throw new InvalidArgumentException('A ledger transaction needs at least two entries.');
        }

        $net = 0;
        foreach ($entries as $e) {
            if (! in_array($e['direction'], ['debit', 'credit'], true)) {
                throw new InvalidArgumentException('Entry direction must be debit or credit.');
            }
            if (! is_int($e['amount']) || $e['amount'] <= 0) {
                throw new InvalidArgumentException('Entry amounts must be positive integers (kobo).');
            }
            $net += $e['direction'] === 'debit' ? $e['amount'] : -$e['amount'];
        }

        if ($net !== 0) {
            throw new InvalidArgumentException("Ledger transaction is unbalanced by {$net}.");
        }
    }
}
