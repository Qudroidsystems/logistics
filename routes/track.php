<?php

use App\Http\Controllers\ProviderPageController;
use App\Http\Controllers\TrackPageController;
use Illuminate\Support\Facades\Route;

/** Public live tracking page, opened from the link sent to the customer or the receiver. */
Route::get('/track/{token}', [TrackPageController::class, 'show'])->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('track');

/** Public page for a listed provider: standing, numbers and reviews. */
Route::get('/p/{slug}', [ProviderPageController::class, 'show'])->where('slug', '[a-z0-9-]+')->middleware('throttle:60,1')->name('provider.public');

/** Public reference for merchants integrating the Partner API. */
Route::get('/developers/partner-api', fn () => view('developers.partner-api', ['base' => url('/api/v1/partner')]))->middleware('throttle:60,1')->name('developers.partner');
