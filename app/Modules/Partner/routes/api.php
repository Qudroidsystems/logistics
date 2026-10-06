<?php

use App\Http\Controllers\Api\Partner\PartnerController;
use Illuminate\Support\Facades\Route;

// Merchant API: bearer API key, not Sanctum.
Route::prefix('v1/partner')->group(function () {
    Route::get('/me', [PartnerController::class, 'me'])->middleware('api.client');
    Route::post('/quotes', [PartnerController::class, 'quote'])->middleware('api.client:quotes');
    Route::post('/deliveries', [PartnerController::class, 'store'])->middleware('api.client:deliveries');
    Route::post('/deliveries/{externalOrderId}/cancel', [PartnerController::class, 'cancel'])->middleware('api.client:deliveries');
});
