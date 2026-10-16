<?php

use App\Http\Controllers\ProviderPageController;
use App\Http\Controllers\TrackPageController;
use Illuminate\Support\Facades\Route;

/** Public live tracking page, opened from the link sent to the customer or the receiver. */
Route::get('/track/{token}', [TrackPageController::class, 'show'])->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('track');
Route::post('/track/{token}/reply', [TrackPageController::class, 'reply'])->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:10,1')->name('track.reply');

/** Public page for a listed provider: standing, numbers and reviews. */
Route::get('/p/{slug}', [ProviderPageController::class, 'show'])->where('slug', '[a-z0-9-]+')->middleware(['marketplace', 'throttle:60,1'])->name('provider.public');

/** Public reference for merchants integrating the Partner API. */
Route::get('/developers/partner-api', fn () => view('developers.partner-api', ['base' => url('/api/v1/partner')]))->middleware('throttle:60,1')->name('developers.partner');
Route::get('/developers/app-api', fn () => view('developers.app-api', ['base' => url('/api/v1')]))->middleware('throttle:60,1')->name('developers.app');

/** One-click stop for campaign emails; the link is signed so it only works for the person it was sent to. */
Route::get('/unsubscribe/{user}', [\App\Http\Controllers\MarketingUnsubscribeController::class, 'show'])->whereNumber('user')->middleware(['signed', 'throttle:20,1'])->name('marketing.unsubscribe');
