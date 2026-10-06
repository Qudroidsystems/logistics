<?php
// app/Services/Accounting/AccountingService.php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AccountingService
{
    /**
     * Create a journal entry with double-entry validation
     */
    public function createJournalEntry(array $data, array $lines)
    {
        return DB::transaction(function () use ($data, $lines) {
            $totalDebit = array_sum(array_column($lines, 'debit'));
            $totalCredit = array_sum(array_column($lines, 'credit'));

            if (abs($totalDebit - $totalCredit) > 0.01) {
                throw new \Exception("Journal entry must balance. Debit: {$totalDebit}, Credit: {$totalCredit}");
            }

            $data['entry_no'] = $this->generateEntryNumber();
            $data['created_by'] = Auth::id();
            $data['entry_date'] = $data['entry_date'] ?? now();

            $journalEntry = JournalEntry::create($data);

            foreach ($lines as $line) {
                $line['journal_entry_id'] = $journalEntry->id;
                JournalEntryLine::create($line);
            }

            return $journalEntry;
        });
    }

    /**
     * Post a journal entry
     */
    public function postEntry($entryId)
    {
        $entry = JournalEntry::findOrFail($entryId);

        if ($entry->status !== 'draft') {
            throw new \Exception('Only draft entries can be posted');
        }

        $entry->update([
            'status' => 'posted',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $entry;
    }

    /**
     * Reverse a journal entry
     */
    public function reverseEntry($entryId, $reason)
    {
        $originalEntry = JournalEntry::findOrFail($entryId);

        if ($originalEntry->status !== 'posted') {
            throw new \Exception('Only posted entries can be reversed');
        }

        return DB::transaction(function () use ($originalEntry, $reason) {
            $reversalLines = [];
            foreach ($originalEntry->lines as $line) {
                $reversalLines[] = [
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'narration' => "Reversal: {$line->narration}",
                    'student_id' => $line->student_id,
                    'staff_id' => $line->staff_id,
                ];
            }

            $reversalEntry = $this->createJournalEntry([
                'entry_date' => now(),
                'entry_type' => 'reversal',
                'description' => "Reversal of: {$originalEntry->description} - Reason: {$reason}",
                'reference_id' => $originalEntry->id,
                'reference_type' => JournalEntry::class,
                'reversal_reason' => $reason,
            ], $reversalLines);

            $this->postEntry($reversalEntry->id);

            $originalEntry->update([
                'status' => 'reversed',
                'reversal_reason' => $reason,
                'reversed_by' => Auth::id(),
                'reversed_at' => now(),
            ]);

            return $reversalEntry;
        });
    }

    /**
     * Generate unique entry number
     */
    private function generateEntryNumber()
    {
        $year = date('Y');
        $lastEntry = JournalEntry::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastEntry ? intval(substr($lastEntry->entry_no, -5)) + 1 : 1;

        return "JE-{$year}-" . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get account balance as at a specific date
     */
    public function getAccountBalance($accountId, $asAtDate = null)
    {
        $asAtDate = $asAtDate ?? now()->format('Y-m-d');

        $result = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->where('jel.account_id', $accountId)
            ->where('je.entry_date', '<=', $asAtDate)
            ->where('je.status', 'posted')
            ->select(
                DB::raw('COALESCE(SUM(jel.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(jel.credit), 0) as total_credit')
            )
            ->first();

        $account = DB::table('chart_of_accounts')->where('id', $accountId)->first();

        if ($account && $account->normal_balance === 'debit') {
            return ($result->total_debit ?? 0) - ($result->total_credit ?? 0);
        }

        return ($result->total_credit ?? 0) - ($result->total_debit ?? 0);
    }

    /**
     * Get account balance for a specific period
     */
    public function getAccountBalanceForPeriod($accountId, $startDate, $endDate)
    {
        $result = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->where('jel.account_id', $accountId)
            ->whereBetween('je.entry_date', [$startDate, $endDate])
            ->where('je.status', 'posted')
            ->select(
                DB::raw('COALESCE(SUM(jel.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(jel.credit), 0) as total_credit')
            )
            ->first();

        $account = DB::table('chart_of_accounts')->where('id', $accountId)->first();

        if ($account && $account->normal_balance === 'debit') {
            return ($result->total_debit ?? 0) - ($result->total_credit ?? 0);
        }

        return ($result->total_credit ?? 0) - ($result->total_debit ?? 0);
    }

    /**
     * Get trial balance
     */
    public function getTrialBalance($asAtDate = null)
    {
        $asAtDate = $asAtDate ?? now()->format('Y-m-d');

        $accounts = DB::table('chart_of_accounts')
            ->where('is_active', true)
            ->get();

        $trialBalance = [];

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $asAtDate);

            if ($balance != 0) {
                $trialBalance[] = [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $account->account_type,
                    'debit' => $balance > 0 && $account->normal_balance === 'debit' ? abs($balance) : 0,
                    'credit' => $balance > 0 && $account->normal_balance === 'credit' ? abs($balance) : 0,
                    'balance' => $balance,
                ];
            }
        }

        return $trialBalance;
    }

    /**
     * Get all accounts by type
     */
    public function getAccountsByType($type)
    {
        return DB::table('chart_of_accounts')
            ->where('account_type', $type)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();
    }
}


