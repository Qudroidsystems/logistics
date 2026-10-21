<?php

use App\Http\Controllers\Api\Customer\BookingController as B;
use Illuminate\Support\Facades\Route;

// Instant prices: public, and booking one when signed in.
Route::get('v1/public/quote-options', [\App\Http\Controllers\Api\InstantQuoteApiController::class, 'options'])->middleware('throttle:60,1');
Route::post('v1/public/quote', [\App\Http\Controllers\Api\InstantQuoteApiController::class, 'quote'])->middleware('throttle:30,1');
Route::get('v1/public/dropoff-centres', [\App\Http\Controllers\Api\InstantQuoteApiController::class, 'centres'])->middleware('throttle:60,1');
Route::post('v1/customer/instant-bookings', [\App\Http\Controllers\Api\InstantQuoteApiController::class, 'book'])->middleware(['auth:sanctum', 'throttle:20,1']);

Route::middleware(['auth:sanctum', 'throttle:90,1'])->prefix('v1')->group(function () {
    Route::prefix('customer')->group(function () {
        Route::get('/addresses', [B::class, 'addresses']);
        Route::post('/addresses', [B::class, 'saveAddress']);
        Route::put('/addresses/{address}', [B::class, 'saveAddress']);
        Route::delete('/addresses/{address}', [B::class, 'deleteAddress']);

        Route::get('/requests/{serviceRequest}/attachments', [B::class, 'attachments']);
        Route::post('/requests/{serviceRequest}/attachments', [B::class, 'upload'])->middleware('throttle:20,1');
        Route::delete('/attachments/{attachment}', [B::class, 'deleteAttachment']);

        Route::get('/agreements/{agreement}/payment', [B::class, 'paymentSummary']);
        Route::post('/agreements/{agreement}/promo', [B::class, 'applyPromo'])->middleware('throttle:10,1');
        Route::delete('/agreements/{agreement}/promo', [B::class, 'removePromo']);
        Route::post('/agreements/{agreement}/points', [B::class, 'usePoints']);
        Route::post('/agreements/{agreement}/gift-card', [B::class, 'applyGiftCard'])->middleware('throttle:10,1');
        Route::delete('/agreements/{agreement}/gift-card', [B::class, 'removeGiftCard']);

        // Loyalty points and gift cards
        Route::get('/loyalty', [\App\Http\Controllers\Api\Customer\RewardsController::class, 'loyalty']);
        Route::get('/gift-cards', [\App\Http\Controllers\Api\Customer\RewardsController::class, 'giftCards']);
        Route::post('/gift-cards', [\App\Http\Controllers\Api\Customer\RewardsController::class, 'buy'])->middleware('throttle:10,1');
        Route::post('/gift-cards/check', [\App\Http\Controllers\Api\Customer\RewardsController::class, 'check'])->middleware('throttle:10,1');

        Route::get('/orders/{shipment}/receipt', [B::class, 'receipt'])->middleware('throttle:20,1');

        // Business plans
        Route::get('/plan', [\App\Http\Controllers\Api\Customer\SubscriptionApiController::class, 'show']);
        Route::post('/plan', [\App\Http\Controllers\Api\Customer\SubscriptionApiController::class, 'subscribe'])->middleware('throttle:10,1');
        Route::post('/plan/change', [\App\Http\Controllers\Api\Customer\SubscriptionApiController::class, 'change'])->middleware('throttle:10,1');
        Route::post('/plan/cancel', [\App\Http\Controllers\Api\Customer\SubscriptionApiController::class, 'cancel']);
        Route::post('/plan/resume', [\App\Http\Controllers\Api\Customer\SubscriptionApiController::class, 'resume']);
        Route::post('/plan/pay', [\App\Http\Controllers\Api\Customer\SubscriptionApiController::class, 'pay'])->middleware('throttle:10,1');
    });

    // Customer, invited provider, the job's driver or staff.
    Route::get('/attachments/{attachment}', [B::class, 'showAttachment'])->where('attachment', '[A-Za-z0-9]{26}');
});
