<?php

use App\Http\Controllers\ProviderPageController;
use App\Http\Controllers\TrackPageController;
use Illuminate\Support\Facades\Route;

/** Public live tracking page, opened from the link sent to the customer or the receiver. */
// Instant price, booking and "track your order" (public pages).
Route::post('/quote', [\App\Http\Controllers\InstantBookingController::class, 'quote'])->middleware('throttle:30,1')->name('quote');
Route::get('/embed/quote', [\App\Http\Controllers\InstantBookingController::class, 'embed'])->middleware('throttle:60,1')->name('quote.embed');
Route::get('/book/{quote}', [\App\Http\Controllers\InstantBookingController::class, 'details'])->where('quote', '[A-Za-z0-9]{26}')->name('book.details');
Route::post('/book/{quote}', [\App\Http\Controllers\InstantBookingController::class, 'book'])->where('quote', '[A-Za-z0-9]{26}')->middleware(['auth', 'throttle:20,1'])->name('book.store');
// Business plans (prices) and partner stores ("become a partner" and the counter screen).
// Drive with us: apply, the terms, and checking on an application.
Route::get('/drive', [\App\Http\Controllers\DriveController::class, 'show'])->name('drive');
Route::post('/drive', [\App\Http\Controllers\DriveController::class, 'store'])->middleware('throttle:5,10')->name('drive.store');
Route::get('/drive/terms', [\App\Http\Controllers\DriveController::class, 'terms'])->name('drive.terms');
Route::get('/drive/applications/{application}', [\App\Http\Controllers\DriveController::class, 'status'])->where('application', '[A-Za-z0-9]{26}')->middleware('throttle:30,1')->name('drive.status');
Route::get('/business', [\App\Http\Controllers\InstantBookingController::class, 'plans'])->name('business');
Route::get('/partners', [\App\Http\Controllers\PartnerApplicationController::class, 'show'])->name('partners');
Route::post('/partners', [\App\Http\Controllers\PartnerApplicationController::class, 'store'])->middleware('throttle:5,1')->name('partners.store');
Route::middleware('auth')->prefix('counter')->name('counter')->group(function () {
    Route::get('/', [\App\Http\Controllers\Partner\CounterController::class, 'index']);
    Route::post('/receive', [\App\Http\Controllers\Partner\CounterController::class, 'receive'])->middleware('throttle:60,1')->name('.receive');
    Route::post('/collect', [\App\Http\Controllers\Partner\CounterController::class, 'collect'])->middleware('throttle:60,1')->name('.collect');
    Route::post('/staff', [\App\Http\Controllers\Partner\CounterController::class, 'addStaff'])->middleware('throttle:10,1')->name('.staff');
});
Route::get('/track', [\App\Http\Controllers\InstantBookingController::class, 'track'])->middleware('throttle:30,1')->name('track.lookup');
Route::get('/track/{token}', [TrackPageController::class, 'show'])->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('track');
Route::post('/track/{token}/reply', [TrackPageController::class, 'reply'])->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:10,1')->name('track.reply');

/** Public page for a listed provider: standing, numbers and reviews. */
Route::get('/p/{slug}', [ProviderPageController::class, 'show'])->where('slug', '[a-z0-9-]+')->middleware(['marketplace', 'throttle:60,1'])->name('provider.public');

/** Public reference for merchants integrating the Partner API. */
Route::get('/pay/etransfer/{reference}', [\App\Http\Controllers\ETransferPageController::class, 'show'])->where('reference', '[A-Z]{2}-[A-Z0-9]{10,40}')->middleware('throttle:60,1')->name('etransfer.show');
Route::post('/pay/etransfer/{reference}/sent', [\App\Http\Controllers\ETransferPageController::class, 'sent'])->where('reference', '[A-Z]{2}-[A-Z0-9]{10,40}')->middleware('throttle:10,1')->name('etransfer.sent');
Route::get('/developers/partner-api', fn () => view('developers.partner-api', ['base' => url('/api/v1/partner')]))->middleware('throttle:60,1')->name('developers.partner');
Route::get('/developers/app-api', fn () => view('developers.app-api', ['base' => url('/api/v1')]))->middleware('throttle:60,1')->name('developers.app');

/** One-click stop for campaign emails; the link is signed so it only works for the person it was sent to. */
Route::get('/unsubscribe/{user}', [\App\Http\Controllers\MarketingUnsubscribeController::class, 'show'])->whereNumber('user')->middleware(['signed', 'throttle:20,1'])->name('marketing.unsubscribe');
