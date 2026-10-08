<?php

use App\Http\Controllers\Api\Marketplace\NegotiationController;
use App\Http\Controllers\Api\Marketplace\ShoppingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:90,1'])->prefix('v1')->group(function () {
    Route::prefix('customer')->group(function () {
        Route::get('/providers', [NegotiationController::class, 'providers']);
        Route::get('/requests', [NegotiationController::class, 'myRequests']);
        Route::post('/requests', [NegotiationController::class, 'createRequest']);
        Route::get('/requests/{serviceRequest}/offers', [NegotiationController::class, 'offers']);
        Route::get('/requests/{serviceRequest}/providers', [NegotiationController::class, 'invited']);
        Route::get('/negotiations/{thread}', [NegotiationController::class, 'customerThread']);
        Route::post('/negotiations/{thread}/counter', [NegotiationController::class, 'customerCounter']);
        Route::post('/negotiations/{thread}/accept', [NegotiationController::class, 'customerAccept']);
        Route::post('/negotiations/{thread}/reject', [NegotiationController::class, 'customerReject']);
        Route::post('/negotiations/{thread}/messages', [NegotiationController::class, 'say']);

        Route::get('/agreements/{agreement}/shopping', [ShoppingController::class, 'show']);
        Route::post('/receipts/{receipt}/review', [ShoppingController::class, 'reviewReceipt']);
        Route::post('/budget-amendments/{amendment}/approve', [ShoppingController::class, 'approveAmendment']);
        Route::post('/budget-amendments/{amendment}/decline', [ShoppingController::class, 'declineAmendment']);
    });

    Route::prefix('provider')->group(function () {
        Route::get('/requests', [NegotiationController::class, 'inbox']);
        Route::post('/requests/{serviceRequest}/offer', [NegotiationController::class, 'offer']);
        Route::get('/negotiations/{thread}', [NegotiationController::class, 'providerThread']);
        Route::post('/negotiations/{thread}/counter', [NegotiationController::class, 'providerCounter']);
        Route::post('/negotiations/{thread}/accept', [NegotiationController::class, 'providerAccept']);
        Route::post('/negotiations/{thread}/messages', [NegotiationController::class, 'say']);

        Route::post('/agreements/{agreement}/advance', [ShoppingController::class, 'advance']);
        Route::post('/agreements/{agreement}/receipts', [ShoppingController::class, 'receipt']);
        Route::post('/agreements/{agreement}/budget-amendments', [ShoppingController::class, 'requestAmendment']);
    });
});
