<?php

use App\Http\Controllers\Api\Admin\AdminFinanceController;
use App\Http\Controllers\Api\Provider\ProviderPayoutController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::prefix('provider')->group(function () {
        Route::get('/wallet', [ProviderPayoutController::class, 'wallet']);
        Route::get('/payouts', [ProviderPayoutController::class, 'payouts']);
        Route::post('/payouts', [ProviderPayoutController::class, 'request']);
        Route::get('/bank-accounts', [ProviderPayoutController::class, 'bankAccounts']);
        Route::post('/bank-accounts', [ProviderPayoutController::class, 'addBankAccount']);
        Route::post('/shipments/{shipment}/cancel', [ProviderPayoutController::class, 'cancelShipment']);
    });

    Route::prefix('admin')->group(function () {
        Route::get('/payouts', [AdminFinanceController::class, 'payouts'])->middleware('permission:Approve withdrawal');
        Route::post('/payouts/{payout}/approve', [AdminFinanceController::class, 'approvePayout'])->middleware('permission:Approve withdrawal');
        Route::post('/payouts/{payout}/reject', [AdminFinanceController::class, 'rejectPayout'])->middleware('permission:Approve withdrawal');
        Route::post('/shipments/{shipment}/cancel', [AdminFinanceController::class, 'cancelShipment'])->middleware('permission:Cancel delivery');
        Route::post('/orders/{order}/refund-to-card', [AdminFinanceController::class, 'refundToCard'])->middleware('permission:Refund payment');
    });
});
