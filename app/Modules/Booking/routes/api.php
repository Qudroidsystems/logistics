<?php

use App\Http\Controllers\Api\Customer\BookingController as B;
use Illuminate\Support\Facades\Route;

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

        Route::get('/orders/{shipment}/receipt', [B::class, 'receipt'])->middleware('throttle:20,1');
    });

    // Customer, invited provider, the job's driver or staff.
    Route::get('/attachments/{attachment}', [B::class, 'showAttachment'])->where('attachment', '[A-Za-z0-9]{26}');
});
