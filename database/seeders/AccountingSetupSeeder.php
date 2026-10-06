<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts the automatic postings need (payroll, loans, cooperative, assets),
 * and a default ledger account for each expense category. Safe to re-run:
 * existing accounts and existing category links are never changed.
 */
class AccountingSetupSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('chart_of_accounts')) return;

        $accounts = [
            // code, name, type, normal, parent
            ['1000', 'Current Assets', 'asset', 'debit', null],
            ['1010', 'Cash in Hand', 'asset', 'debit', '1000'],
            ['1020', 'Bank Account - Main', 'asset', 'debit', '1000'],
            ['1030', 'Accounts Receivable - Student Fees', 'asset', 'debit', '1000'],
            ['1050', 'Staff Loans Receivable', 'asset', 'debit', '1000'],
            ['1100', 'Fixed Assets', 'asset', 'debit', null],
            ['1101', 'Land & Buildings', 'asset', 'debit', '1100'],
            ['1102', 'Furniture & Equipment', 'asset', 'debit', '1100'],
            ['1103', 'Vehicles', 'asset', 'debit', '1100'],
            ['1104', 'Computers & IT Equipment', 'asset', 'debit', '1100'],
            ['1110', 'Accumulated Depreciation', 'asset', 'credit', '1100'],
            ['2000', 'Current Liabilities', 'liability', 'credit', null],
            ['2010', 'Accounts Payable', 'liability', 'credit', '2000'],
            ['2020', 'Staff Payables (Net Pay)', 'liability', 'credit', '2000'],
            ['2100', 'PAYE Payable', 'liability', 'credit', '2000'],
            ['2110', 'Pension Payable - Employee', 'liability', 'credit', '2000'],
            ['2111', 'Pension Payable - Employer', 'liability', 'credit', '2000'],
            ['2120', 'NHF Payable', 'liability', 'credit', '2000'],
            ['2130', 'NSITF Payable', 'liability', 'credit', '2000'],
            ['2150', 'NHIA Payable', 'liability', 'credit', '2000'],
            ['2155', 'ITF Payable', 'liability', 'credit', '2000'],
            ['2160', 'Cooperative Savings Held', 'liability', 'credit', '2000'],
            ['2170', 'Other Payroll Deductions Payable', 'liability', 'credit', '2000'],
            ['3000', 'Equity', 'equity', 'credit', null],
            ['3010', 'Capital Introduced / Opening Balances', 'equity', 'credit', '3000'],
            ['3020', 'Retained Earnings', 'equity', 'credit', '3000'],
            ['4000', 'School Fees Income', 'income', 'credit', null],
            ['4070', 'Other Income', 'income', 'credit', null],
            ['4095', 'Staff Loan Interest Income', 'income', 'credit', null],
            ['4100', 'Gain on Asset Disposal', 'income', 'credit', null],
            ['5000', 'Staff Salaries', 'expense', 'debit', null],
            ['5001', 'Employer Pension & Statutory Costs', 'expense', 'debit', null],
            ['5080', 'Depreciation', 'expense', 'debit', null],
            ['5081', 'Loss on Asset Disposal', 'expense', 'debit', null],
            ['5110', 'General & Administrative', 'expense', 'debit', null],
            ['5120', 'Staff Loans Written Off', 'expense', 'debit', null],
            ['5130', 'Cooperative Dividend', 'expense', 'debit', null],
            ['5140', 'Security', 'expense', 'debit', null],
            ['5150', 'Cleaning & Sanitation', 'expense', 'debit', null],
            ['5160', 'Transport & Fuel', 'expense', 'debit', null],
            ['5170', 'Medical', 'expense', 'debit', null],
            ['5180', 'Professional Fees', 'expense', 'debit', null],
            ['5190', 'Software & Internet', 'expense', 'debit', null],
        ];

        $made = 0;
        foreach ($accounts as [$code, $name, $type, $normal, $parent]) {
            if (ChartOfAccount::where('account_code', $code)->exists()) continue;
            ChartOfAccount::create([
                'account_code' => $code, 'account_name' => $name, 'account_type' => $type, 'normal_balance' => $normal,
                'parent_id' => $parent ? ChartOfAccount::where('account_code', $parent)->value('id') : null,
                'is_bank_account' => $code === '1020', 'is_active' => true,
            ]);
            $made++;
        }

        // Link expense categories that have no account yet.
        $linked = 0;
        if (Schema::hasTable('expense_categories')) {
            $map = [
                'UTIL' => '5010', 'MAINT' => '5020', 'TEACH' => '5030', 'STAFF-001' => '5000', 'STAFF-002' => '5050', 'STAFF' => '5110',
                'MKT' => '5060', 'INS' => '5070', 'MISC-001' => '5040', 'SEC' => '5140', 'CLN' => '5150', 'TRANS' => '5160',
                'MED' => '5170', 'PROF' => '5180', 'SOFT' => '5190',
            ];
            foreach (DB::table('expense_categories')->whereNull('account_id')->get(['id', 'code']) as $c) {
                $code = $map[$c->code] ?? $map[explode('-', (string) $c->code)[0]] ?? '5110';
                $id = ChartOfAccount::where('account_code', $code)->value('id') ?? ChartOfAccount::where('account_code', '5110')->value('id');
                if ($id) { DB::table('expense_categories')->where('id', $c->id)->update(['account_id' => $id]); $linked++; }
            }
        }

        $this->command?->info("  ✅ Accounting: {$made} account(s) added, {$linked} expense categor(ies) linked");
    }
}
