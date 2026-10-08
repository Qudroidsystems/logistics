<?php

use App\Http\Controllers\Merchant\PortalController as P;
use Illuminate\Support\Facades\Route;

/** The merchant's own page: orders, test API keys and delivery-update webhooks. Membership is checked inside the controller. */
Route::middleware('auth')->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/', [P::class, 'home'])->name('home');
    Route::get('/api', [P::class, 'api'])->name('api');
    Route::post('/api/keys', [P::class, 'issueKey'])->middleware('throttle:10,1')->name('key.issue');
    Route::delete('/api/keys/{client}', [P::class, 'revokeKey'])->whereNumber('client')->name('key.revoke');
    Route::post('/api/live-request', [P::class, 'requestLive'])->middleware('throttle:5,1')->name('live.request');
    Route::post('/api/live-key', [P::class, 'createLiveKey'])->middleware('throttle:5,1')->name('live.key');
    Route::post('/api/webhooks', [P::class, 'addHook'])->middleware('throttle:10,1')->name('hook.add');
    Route::post('/api/webhooks/{hook}/toggle', [P::class, 'toggleHook'])->whereNumber('hook')->name('hook.toggle');
    Route::post('/api/webhooks/{hook}/test', [P::class, 'testHook'])->whereNumber('hook')->middleware('throttle:10,1')->name('hook.test');
});
