<?php

use App\Http\Controllers\Admin\CityZoneController as Z;
use App\Http\Controllers\Admin\MerchantsController as Mc;
use App\Http\Controllers\Admin\OpsConsoleController as C;
use App\Http\Controllers\Admin\SettingsController as St;
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

    Route::get('/refunds', [C::class, 'refunds'])->middleware('can:View payment')->name('refunds');
    Route::get('/settlements', [C::class, 'settlements'])->middleware('can:View settlement')->name('settlements');
    Route::post('/settlements/run', [C::class, 'runSettlements'])->middleware('can:Run settlement')->name('settlements.run');
    Route::post('/settlements/{settlement}/approve', [C::class, 'approveSettlement'])->middleware('can:Run settlement')->name('settlement.approve');

    Route::get('/providers', [C::class, 'providersList'])->name('providers');
    Route::post('/providers/{operator}/refresh-score', [C::class, 'refreshScore'])->name('provider.refresh');
    Route::post('/providers/{operator}/suspend', [C::class, 'suspend'])->name('provider.suspend');
    Route::post('/providers/{operator}/reinstate', [C::class, 'reinstate'])->name('provider.reinstate');

    Route::get('/ratings', [C::class, 'ratings'])->middleware('can:View rating')->name('ratings');
    Route::post('/ratings/{rating}/moderate', [C::class, 'moderate'])->middleware('can:Moderate rating')->whereNumber('rating')->name('rating.moderate');

    Route::get('/risk', [C::class, 'risk'])->middleware('can:Manage fraud flags')->name('risk');
    Route::post('/risk/{event}/review', [C::class, 'reviewRisk'])->middleware('can:Manage fraud flags')->whereNumber('event')->name('risk.review');

    Route::get('/cities', [Z::class, 'index'])->middleware('can:View zone')->name('cities');
    Route::post('/cities', [Z::class, 'storeCity'])->middleware('can:Create zone')->name('city.store');
    Route::get('/cities/{city}', [Z::class, 'city'])->middleware('can:View zone')->whereNumber('city')->name('city');
    Route::put('/cities/{city}', [Z::class, 'updateCity'])->middleware('can:Update zone')->whereNumber('city')->name('city.update');
    Route::post('/cities/{city}/zones', [Z::class, 'storeZone'])->middleware('can:Create zone')->whereNumber('city')->name('zone.store');
    Route::post('/zones/{zone}/toggle', [Z::class, 'toggleZone'])->middleware('can:Update zone')->whereNumber('zone')->name('zone.toggle');

    Route::get('/settings', [St::class, 'index'])->name('settings');
    Route::post('/settings/fee', [St::class, 'saveFee'])->name('settings.fee');
    Route::post('/settings/rules', [St::class, 'saveRules'])->name('settings.rules');

    Route::get('/merchants', [Mc::class, 'index'])->middleware('can:View vendor')->name('merchants');
    Route::post('/merchants', [Mc::class, 'store'])->middleware('can:Create vendor')->name('merchant.store');
    Route::get('/merchants/{merchant}', [Mc::class, 'show'])->middleware('can:View vendor')->whereNumber('merchant')->name('merchant');
    Route::put('/merchants/{merchant}', [Mc::class, 'update'])->middleware('can:Update vendor')->whereNumber('merchant')->name('merchant.update');
    Route::post('/merchants/{merchant}/keys', [Mc::class, 'issueKey'])->middleware('can:Update vendor')->whereNumber('merchant')->name('merchant.key.issue');
    Route::delete('/merchants/{merchant}/keys/{client}', [Mc::class, 'revokeKey'])->middleware('can:Update vendor')->whereNumber(['merchant', 'client'])->name('merchant.key.revoke');
    Route::post('/merchants/{merchant}/webhooks', [Mc::class, 'addWebhook'])->middleware('can:Update vendor')->whereNumber('merchant')->name('merchant.hook.add');
    Route::post('/merchants/{merchant}/webhooks/{hook}/toggle', [Mc::class, 'toggleWebhook'])->middleware('can:Update vendor')->whereNumber(['merchant', 'hook'])->name('merchant.hook.toggle');
});
