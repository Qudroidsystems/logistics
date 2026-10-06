<?php
// app/Providers/ReportServiceProvider.php

namespace App\Providers;

use App\Services\Billing\BillAdjustmentService;
use App\Services\Caching\ReportCacheService;
use App\Services\Audit\ReportAuditService;
use Illuminate\Support\ServiceProvider;

class ReportServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(BillAdjustmentService::class, function ($app) {
            return new BillAdjustmentService();
        });

        $this->app->singleton(ReportCacheService::class, function ($app) {
            return new ReportCacheService();
        });

        $this->app->singleton(ReportAuditService::class, function ($app) {
            return new ReportAuditService();
        });
    }

    public function boot()
    {
        //
    }
}