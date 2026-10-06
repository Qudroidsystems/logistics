<?php

/*
|--------------------------------------------------------------------------
| Finance operations: salary payouts, loans, cooperative, attendance pay,
| expenses, purchasing, budgets, assets and the general ledger.
| Loaded from routes/web.php (require __DIR__ . '/finance.php').
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Accounting\AccountingController;
use App\Http\Controllers\Finance\FinancialAuditController;
use App\Http\Controllers\Finance\AssetController;
use App\Http\Controllers\Finance\AttendancePayController;
use App\Http\Controllers\Finance\BudgetController;
use App\Http\Controllers\Finance\CoopController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\LoanController;
use App\Http\Controllers\Finance\MyFinanceController;
use App\Http\Controllers\Finance\PayoutController;
use App\Http\Controllers\Finance\PurchaseRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // ── Payroll: payouts, loans, cooperative, attendance & duty claims ──
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts');
        Route::get('/payouts/month/{period}', [PayoutController::class, 'create'])->whereNumber('period')->name('payouts.create');
        Route::post('/payouts/month/{period}', [PayoutController::class, 'prepare'])->whereNumber('period')->name('payouts.prepare');
        Route::get('/payouts/{batch}', [PayoutController::class, 'show'])->whereNumber('batch')->name('payouts.show');
        Route::post('/payouts/{batch}/release', [PayoutController::class, 'release'])->whereNumber('batch')->middleware('throttle:10,1')->name('payouts.release');
        Route::post('/payouts/{batch}/refresh', [PayoutController::class, 'refresh'])->whereNumber('batch')->name('payouts.refresh');
        Route::post('/payouts/{batch}/manual', [PayoutController::class, 'manual'])->whereNumber('batch')->name('payouts.manual');
        Route::post('/payouts/{batch}/retry', [PayoutController::class, 'retry'])->whereNumber('batch')->name('payouts.retry');
        Route::post('/payouts/{batch}/cancel', [PayoutController::class, 'cancel'])->whereNumber('batch')->name('payouts.cancel');
        Route::get('/payouts/{batch}/schedule', [PayoutController::class, 'schedule'])->whereNumber('batch')->name('payouts.schedule');
        Route::post('/payouts/item/{item}/otp', [PayoutController::class, 'otp'])->whereNumber('item')->middleware('throttle:10,1')->name('payouts.otp');

        Route::get('/loans', [LoanController::class, 'index'])->name('loans');
        Route::get('/loans/quote', [LoanController::class, 'quote'])->name('loans.quote');
        Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
        Route::post('/loans/rules', [LoanController::class, 'saveRules'])->name('loans.rules');
        Route::get('/loans/{loan}', [LoanController::class, 'show'])->whereNumber('loan')->name('loans.show');
        foreach (['approve', 'reject', 'disburse', 'repay', 'pause', 'cancel'] as $a) {
            Route::post("/loans/{loan}/{$a}", [LoanController::class, $a])->whereNumber('loan')->name("loans.{$a}");
        }
        Route::post('/loans/{loan}/write-off', [LoanController::class, 'writeOff'])->whereNumber('loan')->name('loans.writeoff');

        Route::get('/cooperative', [CoopController::class, 'index'])->name('coop');
        Route::get('/cooperative/member/{staff}', [CoopController::class, 'show'])->whereNumber('staff')->name('coop.show');
        Route::post('/cooperative/member', [CoopController::class, 'member'])->name('coop.member');
        Route::post('/cooperative/member/{member}/status', [CoopController::class, 'status'])->whereNumber('member')->name('coop.status');
        Route::post('/cooperative/transaction', [CoopController::class, 'transaction'])->name('coop.transaction');
        Route::post('/cooperative/dividend', [CoopController::class, 'dividend'])->name('coop.dividend');

        Route::get('/attendance-pay', [AttendancePayController::class, 'index'])->name('attendance-pay');
        Route::post('/attendance-pay/settings', [AttendancePayController::class, 'saveSettings'])->name('attendance-pay.settings');
        Route::get('/duty-claims', [AttendancePayController::class, 'claims'])->name('claims');
        Route::post('/duty-claims/decide', [AttendancePayController::class, 'decide'])->name('claims.decide');
        Route::post('/duty-claims', [AttendancePayController::class, 'storeClaim'])->name('claims.store');
    });

    // ── Staff self-service (My Pay) ──
    Route::prefix('my-pay')->name('my-pay.')->group(function () {
        Route::get('/loans', [MyFinanceController::class, 'loans'])->name('loans');
        Route::get('/loans/quote', [MyFinanceController::class, 'quote'])->name('loans.quote');
        Route::post('/loans', [MyFinanceController::class, 'apply'])->middleware('throttle:10,1')->name('loans.apply');
        Route::post('/loans/{loan}/guarantee', [MyFinanceController::class, 'guarantee'])->whereNumber('loan')->name('loans.guarantee');
        Route::post('/loans/{loan}/cancel', [MyFinanceController::class, 'cancelLoan'])->whereNumber('loan')->name('loans.cancel');
        Route::get('/cooperative', [MyFinanceController::class, 'cooperative'])->name('cooperative');
        Route::get('/claims', [MyFinanceController::class, 'claims'])->name('claims');
        Route::post('/claims', [MyFinanceController::class, 'claim'])->middleware('throttle:20,1')->name('claims.store');
        Route::delete('/claims/{claim}', [MyFinanceController::class, 'withdrawClaim'])->whereNumber('claim')->name('claims.withdraw');
    });

    // ── Expenses, purchasing, budgets, assets ──
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::get('/expenses/new', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::get('/expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{voucher}', [ExpenseController::class, 'show'])->whereNumber('voucher')->name('expenses.show');
        Route::get('/expenses/{voucher}/edit', [ExpenseController::class, 'edit'])->whereNumber('voucher')->name('expenses.edit');
        Route::put('/expenses/{voucher}', [ExpenseController::class, 'update'])->whereNumber('voucher')->name('expenses.update');
        Route::get('/expenses/{voucher}/receipt', [ExpenseController::class, 'receipt'])->whereNumber('voucher')->name('expenses.receipt');
        foreach (['submit', 'approve', 'reject', 'pay', 'cancel'] as $a) {
            Route::post("/expenses/{voucher}/{$a}", [ExpenseController::class, $a])->whereNumber('voucher')->name("expenses.{$a}");
        }
        Route::get('/vendors', [ExpenseController::class, 'vendors'])->name('vendors');
        Route::post('/vendors', [ExpenseController::class, 'saveVendor'])->name('vendors.save');
        Route::get('/expense-categories', [ExpenseController::class, 'categories'])->name('expense-categories');
        Route::post('/expense-categories', [ExpenseController::class, 'saveCategory'])->name('expense-categories.save');
        Route::post('/expense-settings', [ExpenseController::class, 'saveExpenseSettings'])->name('expense-settings');

        Route::get('/purchases', [PurchaseRequestController::class, 'index'])->name('purchases');
        Route::post('/purchases', [PurchaseRequestController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/{purchase}', [PurchaseRequestController::class, 'show'])->whereNumber('purchase')->name('purchases.show');
        foreach (['approve', 'reject', 'received', 'cancel'] as $a) {
            Route::post("/purchases/{purchase}/{$a}", [PurchaseRequestController::class, $a])->whereNumber('purchase')->name("purchases.{$a}");
        }

        Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets');
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::get('/budgets/{budget}', [BudgetController::class, 'show'])->whereNumber('budget')->name('budgets.show');
        Route::post('/budgets/{budget}/lines', [BudgetController::class, 'saveLines'])->whereNumber('budget')->name('budgets.lines');
        Route::post('/budgets/{budget}/status', [BudgetController::class, 'status'])->whereNumber('budget')->name('budgets.status');
        Route::get('/budgets/{budget}/export', [BudgetController::class, 'export'])->whereNumber('budget')->name('budgets.export');

        Route::get('/assets', [AssetController::class, 'index'])->name('assets');
        Route::get('/assets/export', [AssetController::class, 'export'])->name('assets.export');
        Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
        Route::post('/assets/depreciate', [AssetController::class, 'depreciate'])->name('assets.depreciate');
        Route::get('/assets/{asset}', [AssetController::class, 'show'])->whereNumber('asset')->name('assets.show');
        Route::put('/assets/{asset}', [AssetController::class, 'update'])->whereNumber('asset')->name('assets.update');
        Route::post('/assets/{asset}/dispose', [AssetController::class, 'dispose'])->whereNumber('asset')->name('assets.dispose');
    });

    // ── General ledger ──
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/', [AccountingController::class, 'dashboard'])->name('dashboard');
        Route::get('/accounts', [AccountingController::class, 'accounts'])->name('accounts');
        Route::post('/accounts', [AccountingController::class, 'saveAccount'])->name('accounts.save');
        Route::get('/journal', [AccountingController::class, 'journals'])->name('journals');
        Route::get('/journal/new', [AccountingController::class, 'journalCreate'])->name('journals.create');
        Route::post('/journal', [AccountingController::class, 'journalStore'])->name('journals.store');
        Route::get('/journal/{entry}', [AccountingController::class, 'journalShow'])->whereNumber('entry')->name('journals.show');
        Route::post('/journal/{entry}/post', [AccountingController::class, 'journalPost'])->whereNumber('entry')->name('journals.post');
        Route::post('/journal/{entry}/reverse', [AccountingController::class, 'journalReverse'])->whereNumber('entry')->name('journals.reverse');
        Route::get('/ledger', [AccountingController::class, 'ledger'])->name('ledger');
        Route::get('/trial-balance', [AccountingController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/income-statement', [AccountingController::class, 'incomeStatement'])->name('income-statement');
        Route::get('/balance-sheet', [AccountingController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/cash-flow', [AccountingController::class, 'cashFlow'])->name('cash-flow');
        Route::post('/settings', [AccountingController::class, 'saveSettings'])->name('settings');
        Route::post('/sync-fees', [AccountingController::class, 'syncFees'])->name('sync-fees');
        Route::post('/catch-up', [AccountingController::class, 'catchUp'])->name('catch-up');
    });

    // ── Financial audit: trail, exceptions, analysis (accountant's audit) ──
    Route::prefix('finance/audit')->name('finance.audit.')->group(function () {
        Route::get('/', [FinancialAuditController::class, 'dashboard'])->name('dashboard');
        Route::get('/trail', [FinancialAuditController::class, 'trail'])->name('trail');
        Route::get('/trail/export', [FinancialAuditController::class, 'exportTrail'])->name('trail.export');
        Route::get('/exceptions', [FinancialAuditController::class, 'exceptions'])->name('exceptions');
        Route::post('/exceptions/review', [FinancialAuditController::class, 'review'])->name('exceptions.review');
        Route::get('/users', [FinancialAuditController::class, 'users'])->name('users');
    });
});
