<?php

use App\Http\Controllers\Api\Customer\DeliveryController;
use App\Http\Controllers\Api\RatingController;
use Illuminate\Support\Facades\Route;

// Public provider page (listed, active providers only).
Route::get('/v1/providers/{slug}', [RatingController::class, 'providerPage'])->middleware('throttle:60,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::prefix('customer')->group(function () {
        Route::post('/shipments/{shipment}/confirm', [DeliveryController::class, 'confirm']);
        Route::post('/shipments/{shipment}/object', [DeliveryController::class, 'object']);
        Route::post('/shipments/{shipment}/rating', [DeliveryController::class, 'rate']);
    });
    Route::post('/provider/shipments/{shipment}/rate-customer', [RatingController::class, 'rateCustomer']);
});
