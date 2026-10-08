<?php

use App\Http\Controllers\Api\Customer\CancellationController;
use App\Http\Controllers\Api\Customer\CustomerWalletController;
use App\Http\Controllers\Api\Customer\TrackingController;
use App\Http\Controllers\Api\Driver\DriverJobController;
use App\Http\Controllers\Api\Driver\DriverWorkController;
use Illuminate\Support\Facades\Route;

// Public, token-guarded tracking page data.
Route::get('/v1/track/{token}', [TrackingController::class, 'show'])->middleware('throttle:60,1');

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('driver')->middleware('throttle:120,1')->group(function () {
        Route::get('/me', [DriverWorkController::class, 'me']);
        Route::post('/availability', [DriverWorkController::class, 'availability']);
        Route::get('/offers', [DriverWorkController::class, 'offers']);
        Route::post('/offers/{offer}/accept', [DriverWorkController::class, 'accept'])->whereNumber('offer');
        Route::post('/offers/{offer}/decline', [DriverWorkController::class, 'decline'])->whereNumber('offer');
        Route::get('/jobs', [DriverWorkController::class, 'jobs']);
        Route::get('/jobs/{shipment}', [DriverWorkController::class, 'job']);
        Route::post('/jobs/{shipment}/start', [DriverWorkController::class, 'start']);
        Route::post('/jobs/{shipment}/release', [DriverWorkController::class, 'release']);
        Route::post('/jobs/{shipment}/issue', [DriverWorkController::class, 'issue']);
        Route::get('/earnings', [DriverWorkController::class, 'earnings']);
        Route::post('/location', [DriverJobController::class, 'location']);
        Route::post('/stops/{stop}/complete', [DriverJobController::class, 'completeStop']);
    });
    Route::prefix('customer')->group(function () {
        Route::get('/shipments/{shipment}/delivery-code', [TrackingController::class, 'deliveryCode']);
        Route::get('/shipments/{shipment}/cancel-preview', [CancellationController::class, 'preview']);
        Route::post('/shipments/{shipment}/cancel', [CancellationController::class, 'cancel']);

        Route::get('/wallet', [CustomerWalletController::class, 'show']);
        Route::post('/wallet/top-up', [CustomerWalletController::class, 'topUp']);
        Route::get('/bank-accounts', [CustomerWalletController::class, 'bankAccounts']);
        Route::post('/bank-accounts', [CustomerWalletController::class, 'addBankAccount']);
        Route::post('/wallet/withdraw', [CustomerWalletController::class, 'withdraw']);
        Route::post('/agreements/{agreement}/pay', [CustomerWalletController::class, 'payAgreement']);
    });
});
