<?php

use App\Http\Controllers\TrackPageController;
use Illuminate\Support\Facades\Route;

/** Public live tracking page, opened from the link sent to the customer or the receiver. */
Route::get('/track/{token}', [TrackPageController::class, 'show'])->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('track');
