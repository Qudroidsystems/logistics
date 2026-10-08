<?php

use App\Http\Controllers\Driver\HomeController as D;
use Illuminate\Support\Facades\Route;

/** The driver's phone pages. Only people with an active driver profile get past the controller. */
Route::middleware('auth')->prefix('driver')->name('driver.')->group(function () {
    Route::get('/', [D::class, 'home'])->name('home');
    Route::post('/availability', [D::class, 'availability'])->name('availability');
    Route::post('/offers/{offer}/accept', [D::class, 'accept'])->whereNumber('offer')->name('offer.accept');
    Route::post('/offers/{offer}/decline', [D::class, 'decline'])->whereNumber('offer')->name('offer.decline');
    Route::get('/jobs/{shipment}', [D::class, 'job'])->name('job');
    Route::post('/jobs/{shipment}/start', [D::class, 'start'])->name('job.start');
    Route::post('/jobs/{shipment}/stops/{stop}/complete', [D::class, 'completeStop'])->whereNumber('stop')->name('stop.complete');
    Route::post('/jobs/{shipment}/release', [D::class, 'release'])->name('job.release');
    Route::post('/jobs/{shipment}/issue', [D::class, 'issue'])->name('job.issue');
    Route::post('/location', [D::class, 'location'])->middleware('throttle:120,1')->name('location');
    Route::get('/history', [D::class, 'history'])->name('history');
});
