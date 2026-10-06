<?php
// database/seeders/ChartOfAccountsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Assets (1000-1999)
            ['account_code' => '1000', 'account_name' => 'Current Assets', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['account_code' => '1010', 'account_name' => 'Cash in Hand', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000'],
            ['account_code' => '1020', 'account_name' => 'Bank Account - Main', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000', 'is_bank_account' => true],
            ['account_code' => '1030', 'account_name' => 'Accounts Receivable - Student Fees', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000'],
            ['account_code' => '1040', 'account_name' => 'Prepaid Expenses', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000'],
            ['account_code' => '1050', 'account_name' => 'Staff Loans Receivable', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000'],
            ['account_code' => '1060', 'account_name' => 'Inventory/Supplies', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000'],

            // Fixed Assets
            ['account_code' => '1100', 'account_name' => 'Fixed Assets', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['account_code' => '1101', 'account_name' => 'Land & Buildings', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100'],
            ['account_code' => '1102', 'account_name' => 'Furniture & Equipment', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100'],
            ['account_code' => '1103', 'account_name' => 'Vehicles', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100'],
            ['account_code' => '1104', 'account_name' => 'Computers & IT Equipment', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100'],
            ['account_code' => '1110', 'account_name' => 'Accumulated Depreciation', 'account_type' => 'asset', 'normal_balance' => 'credit', 'parent_code' => '1100'],

            // Liabilities (2000-2999)
            ['account_code' => '2000', 'account_name' => 'Current Liabilities', 'account_type' => 'liability', 'normal_balance' => 'credit'],
            ['account_code' => '2010', 'account_name' => 'Accounts Payable', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000'],
            ['account_code' => '2020', 'account_name' => 'Staff Payables', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000'],
            ['account_code' => '2030', 'account_name' => 'Unearned Fees', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000'],
            ['account_code' => '2100', 'account_name' => 'PAYE Payable', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000'],
            ['account_code' => '2110', 'account_name' => 'Pension Payable - Employee', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000'],
            ['account_code' => '2111', 'account_name' => 'Pension Payable - Employer', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000'],
            ['account_code' => '2120', 'account_name' => 'NHF Payable', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000'],
            ['account_code' => '2130', 'account_name' => 'NSITF Payable', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000'],
            ['account_code' => '2140', 'account_name' => 'Staff Loans Payable', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000'],

            // Equity (3000-3999)
            ['account_code' => '3000', 'account_name' => 'Equity', 'account_type' => 'equity', 'normal_balance' => 'credit'],
            ['account_code' => '3010', 'account_name' => 'Capital Introduced', 'account_type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3000'],
            ['account_code' => '3020', 'account_name' => 'Retained Earnings', 'account_type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3000'],
            ['account_code' => '3030', 'account_name' => 'Revaluation Reserve', 'account_type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3000'],

            // Income (4000-4999)
            ['account_code' => '4000', 'account_name' => 'School Fees Income', 'account_type' => 'income', 'normal_balance' => 'credit'],
            ['account_code' => '4010', 'account_name' => 'Development Levy', 'account_type' => 'income', 'normal_balance' => 'credit'],
            ['account_code' => '4020', 'account_name' => 'ICT Fees', 'account_type' => 'income', 'normal_balance' => 'credit'],
            ['account_code' => '4030', 'account_name' => 'Sports Fees', 'account_type' => 'income', 'normal_balance' => 'credit'],
            ['account_code' => '4040', 'account_name' => 'Library Fees', 'account_type' => 'income', 'normal_balance' => 'credit'],
            ['account_code' => '4050', 'account_name' => 'Scholarship Write-Off', 'account_type' => 'income', 'normal_balance' => 'credit'],
            ['account_code' => '4060', 'account_name' => 'Discount Given', 'account_type' => 'income', 'normal_balance' => 'credit'],
            ['account_code' => '4070', 'account_name' => 'Other Income', 'account_type' => 'income', 'normal_balance' => 'credit'],
            ['account_code' => '4080', 'account_name' => 'Donations', 'account_type' => 'income', 'normal_balance' => 'credit'],
            ['account_code' => '4090', 'account_name' => 'Interest Income', 'account_type' => 'income', 'normal_balance' => 'credit'],

            // Expenses (5000-5999)
            ['account_code' => '5000', 'account_name' => 'Staff Salaries', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['account_code' => '5010', 'account_name' => 'Utilities', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['account_code' => '5020', 'account_name' => 'Maintenance', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['account_code' => '5030', 'account_name' => 'Teaching Materials', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['account_code' => '5040', 'account_name' => 'Bank Charges', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['account_code' => '5050', 'account_name' => 'Staff Training', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['account_code' => '5060', 'account_name' => 'Marketing & Advertising', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['account_code' => '5070', 'account_name' => 'Insurance', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['account_code' => '5080', 'account_name' => 'Depreciation', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['account_code' => '5090', 'account_name' => 'Scholarship Expense', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['account_code' => '5100', 'account_name' => 'Discount Expense', 'account_type' => 'expense', 'normal_balance' => 'debit'],
        ];

        foreach ($accounts as $account) {
            $parentCode = $account['parent_code'] ?? null;
            $parentId = null;

            if ($parentCode) {
                $parent = ChartOfAccount::where('account_code', $parentCode)->first();
                $parentId = $parent->id ?? null;
            }

            ChartOfAccount::updateOrCreate(
                ['account_code' => $account['account_code']],
                [
                    'account_name' => $account['account_name'],
                    'account_type' => $account['account_type'],
                    'normal_balance' => $account['normal_balance'],
                    'parent_id' => $parentId,
                    'is_bank_account' => $account['is_bank_account'] ?? false,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ Chart of accounts seeded successfully!');
    }
}
