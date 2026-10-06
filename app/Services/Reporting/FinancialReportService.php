<?php
// app/Services/Reporting/FinancialReportService.php

namespace App\Services\Reporting;

use App\Services\Accounting\AccountingService;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Generate Balance Sheet
     */
    public function generateBalanceSheet($asAtDate = null)
    {
        $asAtDate = $asAtDate ?? now()->format('Y-m-d');

        // Get assets from chart of accounts
        $assets = DB::table('chart_of_accounts')
            ->where('account_type', 'asset')
            ->where('is_active', true)
            ->get()
            ->map(function($account) use ($asAtDate) {
                $balance = $this->accountingService->getAccountBalance($account->id, $asAtDate);
                return [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'balance' => $balance,
                ];
            });

        // Get liabilities
        $liabilities = DB::table('chart_of_accounts')
            ->where('account_type', 'liability')
            ->where('is_active', true)
            ->get()
            ->map(function($account) use ($asAtDate) {
                $balance = $this->accountingService->getAccountBalance($account->id, $asAtDate);
                return [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'balance' => $balance,
                ];
            });

        // Get equity
        $equity = DB::table('chart_of_accounts')
            ->where('account_type', 'equity')
            ->where('is_active', true)
            ->get()
            ->map(function($account) use ($asAtDate) {
                $balance = $this->accountingService->getAccountBalance($account->id, $asAtDate);
                return [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'balance' => $balance,
                ];
            });

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance');

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'as_at_date' => $asAtDate,
        ];
    }

    /**
     * Generate Income Statement
     */
    public function generateIncomeStatement($startDate, $endDate)
    {
        // Get income accounts
        $income = DB::table('chart_of_accounts')
            ->where('account_type', 'income')
            ->where('is_active', true)
            ->get()
            ->map(function($account) use ($startDate, $endDate) {
                $balance = $this->accountingService->getAccountBalanceForPeriod($account->id, $startDate, $endDate);
                return [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'amount' => abs($balance),
                ];
            });

        // Get expense accounts
        $expenses = DB::table('chart_of_accounts')
            ->where('account_type', 'expense')
            ->where('is_active', true)
            ->get()
            ->map(function($account) use ($startDate, $endDate) {
                $balance = $this->accountingService->getAccountBalanceForPeriod($account->id, $startDate, $endDate);
                return [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'amount' => abs($balance),
                ];
            });

        $totalIncome = $income->sum('amount');
        $totalExpenses = $expenses->sum('amount');
        $netProfit = $totalIncome - $totalExpenses;

        return [
            'income' => $income,
            'expenses' => $expenses,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * Generate Cash Flow Statement
     */
    public function generateCashFlow($startDate, $endDate)
    {
        // Operating Activities - Cash from income and expenses
        $operatingActivities = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'jel.account_id')
            ->whereBetween('je.entry_date', [$startDate, $endDate])
            ->where('je.status', 'posted')
            ->whereIn('coa.account_type', ['income', 'expense'])
            ->select(DB::raw('coa.account_type, SUM(CASE WHEN coa.normal_balance = "debit" THEN jel.debit - jel.credit ELSE jel.credit - jel.debit END) as net_amount'))
            ->groupBy('coa.account_type')
            ->get();

        $cashInflow = $operatingActivities->where('account_type', 'income')->sum('net_amount');
        $cashOutflow = $operatingActivities->where('account_type', 'expense')->sum('net_amount');
        $operatingCashFlow = $cashInflow - abs($cashOutflow);

        // Investing Activities - Purchase/sale of assets
        $investingActivities = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'jel.account_id')
            ->whereBetween('je.entry_date', [$startDate, $endDate])
            ->where('je.status', 'posted')
            ->where('coa.account_type', 'asset')
            ->where('coa.is_fixed_asset', true)
            ->select(DB::raw('SUM(CASE WHEN coa.normal_balance = "debit" THEN jel.credit - jel.debit ELSE jel.debit - jel.credit END) as net_investing'))
            ->first();

        // Financing Activities - Capital/loan transactions
        $financingActivities = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('chart_of_accounts as coa', 'coa.id', '=', 'jel.account_id')
            ->whereBetween('je.entry_date', [$startDate, $endDate])
            ->where('je.status', 'posted')
            ->where('coa.account_type', 'equity')
            ->select(DB::raw('SUM(CASE WHEN coa.normal_balance = "debit" THEN jel.credit - jel.debit ELSE jel.debit - jel.credit END) as net_financing'))
            ->first();

        $netCashFlow = $operatingCashFlow + ($investingActivities->net_investing ?? 0) + ($financingActivities->net_financing ?? 0);

        return [
            'operating_activities' => $operatingCashFlow,
            'investing_activities' => $investingActivities->net_investing ?? 0,
            'financing_activities' => $financingActivities->net_financing ?? 0,
            'net_cash_flow' => $netCashFlow,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }
}
