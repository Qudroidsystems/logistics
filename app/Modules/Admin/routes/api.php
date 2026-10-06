<?php

use App\Http\Controllers\Api\Admin\AdminOpsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:120,1'])->prefix('v1/admin')->group(function () {
    Route::get('/dashboard', [AdminOpsController::class, 'dashboard'])->middleware('permission:dashboard');

    Route::get('/orders', [AdminOpsController::class, 'orders'])->middleware('permission:View delivery');
    Route::get('/orders/{order}', [AdminOpsController::class, 'order'])->middleware('permission:View delivery');

    Route::get('/disputes', [AdminOpsController::class, 'disputes'])->middleware('permission:View dispute');
    Route::get('/disputes/{dispute}', [AdminOpsController::class, 'dispute'])->middleware('permission:View dispute');
    Route::post('/disputes/{dispute}/decide', [AdminOpsController::class, 'decideDispute'])->middleware('permission:Resolve dispute');

    Route::get('/refunds', [AdminOpsController::class, 'refunds'])->middleware('permission:View payment');
    Route::get('/settlements', [AdminOpsController::class, 'settlements'])->middleware('permission:View settlement');
    Route::post('/settlements/run', [AdminOpsController::class, 'runSettlements'])->middleware('permission:Run settlement');
    Route::post('/settlements/{settlement}/approve', [AdminOpsController::class, 'approveSettlement'])->middleware('permission:Run settlement');

    Route::get('/providers', [AdminOpsController::class, 'providers'])->middleware('permission:View vendor|View driver|View shopper');
    Route::post('/providers/{operator}/refresh-score', [AdminOpsController::class, 'refreshProvider'])->middleware('permission:View vendor|View driver|View shopper');

    Route::get('/ratings', [AdminOpsController::class, 'ratings'])->middleware('permission:View rating');
    Route::post('/ratings/{rating}/moderate', [AdminOpsController::class, 'moderateRating'])->middleware('permission:Moderate rating');

    Route::get('/risk-events', [AdminOpsController::class, 'riskEvents'])->middleware('permission:Manage fraud flags');
    Route::post('/risk-events/{event}/review', [AdminOpsController::class, 'reviewRiskEvent'])->middleware('permission:Manage fraud flags');
});
