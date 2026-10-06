<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Render pagination with the Bootstrap 5 theme (matches the Velzon UI).
        Paginator::useBootstrapFive();

        // Financial audit: record user-made changes to money-related records.
        if (\Illuminate\Support\Facades\Schema::hasTable('financial_audit_logs')) {
            $audited = [
                \App\Models\ExpenseVoucher::class, \App\Models\JournalEntry::class,
                \App\Models\LoanAdvance::class, \App\Models\PayoutItem::class,
                \App\Models\StudentBillPaymentRecord::class, \App\Models\OnlineFeePayment::class,
                \App\Models\StatutoryRemittance::class, \App\Models\DiscountAssignment::class,
                \App\Models\ScholarshipAssignment::class, \App\Models\Budget::class,
                \App\Models\FixedAsset::class, \App\Models\PurchaseRequest::class,
                \App\Models\CoopTransaction::class,
            ];
            foreach ($audited as $model) {
                if (class_exists($model)) {
                    try { $model::observe(\App\Observers\FinancialAuditObserver::class); } catch (\Throwable $e) {}
                }
            }
        }

        // Module feature flags: @feature('key') ... @endfeature (combine with @can).
        if (class_exists(\App\Models\FeatureFlag::class)) {
            Blade::if('feature', fn (string $key) => \App\Models\FeatureFlag::enabled($key));
            Blade::if('featureany', fn (array $keys) => \App\Models\FeatureFlag::anyEnabled($keys));
        }
    }
}
