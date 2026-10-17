<?php

use App\Http\Controllers\Api\Partner\PartnerController;
use Illuminate\Support\Facades\Route;

// Merchant API: bearer API key, not Sanctum.
Route::prefix('v1/partner')->group(function () {
    Route::get('/me', [PartnerController::class, 'me'])->middleware('api.client');
    Route::post('/quotes', [PartnerController::class, 'quote'])->middleware('api.client:quotes');
    Route::post('/quotes/options', [PartnerController::class, 'options'])->middleware(['api.client:quotes', 'throttle:60,1']);
    Route::get('/catalog', [PartnerController::class, 'catalog'])->middleware('api.client');
    Route::get('/deliveries', [PartnerController::class, 'index'])->middleware('api.client:deliveries');
    Route::post('/deliveries/batch', [PartnerController::class, 'batch'])->middleware(['api.client:deliveries', 'throttle:20,1']);
    Route::post('/deliveries', [PartnerController::class, 'store'])->middleware('api.client:deliveries');
    Route::get('/deliveries/{externalOrderId}', [PartnerController::class, 'show'])->middleware('api.client:deliveries');
    Route::get('/deliveries/{externalOrderId}/tracking', [PartnerController::class, 'tracking'])->middleware(['api.client:deliveries', 'throttle:240,1']);
    Route::post('/deliveries/{externalOrderId}/reply', [PartnerController::class, 'reply'])->middleware(['api.client:deliveries', 'throttle:20,1']);
    Route::post('/deliveries/{externalOrderId}/return', [PartnerController::class, 'returnDelivery'])->middleware(['api.client:deliveries', 'throttle:20,1']);
    Route::post('/deliveries/{externalOrderId}/cancel', [PartnerController::class, 'cancel'])->middleware('api.client:deliveries');
});
