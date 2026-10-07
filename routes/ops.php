<?php

use App\Http\Controllers\Admin\OpsConsoleController as C;
use Illuminate\Support\Facades\Route;

/** Staff console for the delivery marketplace. Same permissions as the JSON admin API. */
Route::middleware('auth')->prefix('ops')->name('ops.')->group(function () {
    Route::get('/', [C::class, 'dashboard'])->middleware('can:dashboard')->name('dashboard');

    Route::get('/orders', [C::class, 'orders'])->middleware('can:View delivery')->name('orders');
    Route::get('/orders/{order}', [C::class, 'order'])->middleware('can:View delivery')->name('order');

    Route::get('/disputes', [C::class, 'disputes'])->middleware('can:View dispute')->name('disputes');
    Route::get('/disputes/{dispute}', [C::class, 'dispute'])->middleware('can:View dispute')->name('dispute');
    Route::post('/disputes/{dispute}/decide', [C::class, 'decide'])->middleware('can:Resolve dispute')->name('dispute.decide');

    Route::get('/applications', [C::class, 'applications'])->middleware('can:View kyc')->name('applications');
    Route::get('/applications/{operator}', [C::class, 'application'])->middleware('can:View kyc')->name('application');
    Route::get('/documents/{doc}', [C::class, 'document'])->middleware('can:View kyc')->name('document');
    Route::post('/documents/{doc}/approve', [C::class, 'approveDocument'])->middleware('can:Approve kyc')->name('document.approve');
    Route::post('/documents/{doc}/reject', [C::class, 'rejectDocument'])->middleware('can:Reject kyc')->name('document.reject');
    Route::post('/applications/{operator}/decide', [C::class, 'decideApplication'])->middleware('can:View kyc')->name('application.decide');
});
