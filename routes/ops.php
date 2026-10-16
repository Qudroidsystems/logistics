<?php

use App\Http\Controllers\Admin\BackofficeController as Bo;
use App\Http\Controllers\Admin\CityZoneController as Z;
use App\Http\Controllers\Admin\FailedDeliveriesController as Fd;
use App\Http\Controllers\Admin\MerchantsController as Mc;
use App\Http\Controllers\Admin\OpsConsoleController as C;
use App\Http\Controllers\Admin\PricingAdminController as Pr;
use App\Http\Controllers\Admin\SettingsController as St;
use Illuminate\Support\Facades\Route;

/** Staff console for the delivery marketplace. Same permissions as the JSON admin API. */
Route::middleware('auth')->prefix('ops')->name('ops.')->group(function () {
    Route::get('/', [C::class, 'dashboard'])->middleware('can:dashboard')->name('dashboard');

    Route::get('/orders', [C::class, 'orders'])->middleware('can:View delivery')->name('orders');
    Route::get('/orders/{order}', [C::class, 'order'])->middleware('can:View delivery')->name('order');
    Route::get('/orders/{order}/edit', [Bo::class, 'editJob'])->middleware('can:Update delivery')->name('order.edit');
    Route::put('/orders/{order}', [Bo::class, 'updateJob'])->middleware('can:Update delivery')->name('order.update');
    Route::get('/bookings/new', [Bo::class, 'newBooking'])->middleware('can:Create delivery')->name('booking.new');
    Route::post('/bookings/price', [Bo::class, 'price'])->middleware(['can:Create delivery', 'throttle:30,1'])->name('booking.price');
    Route::post('/bookings', [Bo::class, 'storeBooking'])->middleware(['can:Create delivery', 'throttle:20,1'])->name('booking.store');

    // Reports: each report checks its own permission; exports also need "Export reports".
    Route::get('/reports/{report}/export.{format}', [Bo::class, 'export'])->middleware('can:Export reports')->whereIn('format', ['xlsx', 'pdf'])->name('reports.export');
    Route::get('/reports/{report?}', [Bo::class, 'reports'])->name('reports');

    Route::get('/failed-deliveries', [Fd::class, 'index'])->middleware('can:View delivery')->name('failed');

    Route::get('/disputes', [C::class, 'disputes'])->middleware('can:View dispute')->name('disputes');
    Route::get('/disputes/{dispute}', [C::class, 'dispute'])->middleware('can:View dispute')->name('dispute');
    Route::post('/disputes/{dispute}/decide', [C::class, 'decide'])->middleware('can:Resolve dispute')->name('dispute.decide');

    Route::get('/applications', [C::class, 'applications'])->middleware('can:View kyc')->middleware('marketplace')->name('applications');
    Route::get('/applications/{operator}', [C::class, 'application'])->middleware('can:View kyc')->middleware('marketplace')->name('application');
    Route::get('/documents/{doc}', [C::class, 'document'])->middleware('can:View kyc')->middleware('marketplace')->name('document');
    Route::post('/documents/{doc}/approve', [C::class, 'approveDocument'])->middleware('can:Approve kyc')->middleware('marketplace')->name('document.approve');
    Route::post('/documents/{doc}/reject', [C::class, 'rejectDocument'])->middleware('can:Reject kyc')->middleware('marketplace')->name('document.reject');
    Route::post('/applications/{operator}/decide', [C::class, 'decideApplication'])->middleware('can:View kyc')->middleware('marketplace')->name('application.decide');

    Route::get('/refunds', [C::class, 'refunds'])->middleware('can:View payment')->name('refunds');
    Route::get('/settlements', [C::class, 'settlements'])->middleware('can:View settlement')->name('settlements');
    Route::post('/settlements/run', [C::class, 'runSettlements'])->middleware('can:Run settlement')->name('settlements.run');
    Route::post('/settlements/{settlement}/approve', [C::class, 'approveSettlement'])->middleware('can:Run settlement')->name('settlement.approve');

    Route::get('/providers', [C::class, 'providersList'])->middleware('marketplace')->name('providers');
    Route::post('/providers/{operator}/refresh-score', [C::class, 'refreshScore'])->middleware('marketplace')->name('provider.refresh');
    Route::post('/providers/{operator}/suspend', [C::class, 'suspend'])->middleware('marketplace')->name('provider.suspend');
    Route::post('/providers/{operator}/reinstate', [C::class, 'reinstate'])->middleware('marketplace')->name('provider.reinstate');

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

    Route::get('/promos', [Pr::class, 'promos'])->middleware('can:View promo code')->name('promos');
    Route::post('/promos', [Pr::class, 'storePromo'])->middleware('can:Create promo code')->name('promo.store');
    Route::post('/promos/{promo}/toggle', [Pr::class, 'togglePromo'])->middleware('can:Update promo code')->whereNumber('promo')->name('promo.toggle');
    Route::post('/referrals', [Pr::class, 'saveReferrals'])->middleware('can:Update promo code')->name('referrals.save');
    Route::get('/campaigns', [\App\Http\Controllers\Admin\CampaignsController::class, 'index'])->middleware('can:View promo code')->name('campaigns');
    Route::post('/campaigns', [\App\Http\Controllers\Admin\CampaignsController::class, 'store'])->middleware('can:Create promo code')->name('campaigns.store');
    Route::post('/campaigns/{campaign}', [\App\Http\Controllers\Admin\CampaignsController::class, 'update'])->middleware('can:Create promo code')->whereNumber('campaign')->name('campaigns.update');
    Route::post('/campaigns/{campaign}/{action}', [\App\Http\Controllers\Admin\CampaignsController::class, 'action'])->middleware(['can:Create promo code', 'throttle:20,1'])->whereNumber('campaign')->whereIn('action', ['test', 'send', 'cancel'])->name('campaigns.action');
    Route::post('/loyalty', [Pr::class, 'saveLoyalty'])->middleware('can:Update promo code')->name('loyalty.save');
    Route::post('/gift-cards', [Pr::class, 'issueGiftCard'])->middleware('can:Create promo code')->name('giftcards.issue');
    Route::post('/gift-cards/{card}/toggle', [Pr::class, 'toggleGiftCard'])->middleware('can:Update promo code')->whereNumber('card')->name('giftcards.toggle');
    Route::post('/corporate-discounts', [Pr::class, 'storeCorporate'])->middleware('can:Create promo code')->name('corporate.store');
    Route::post('/corporate-discounts/{discount}/toggle', [Pr::class, 'toggleCorporate'])->middleware('can:Update promo code')->whereNumber('discount')->name('corporate.toggle');
    Route::get('/price-lists', [Pr::class, 'plans'])->middleware('can:Manage pricing rules')->name('price-lists');
    Route::post('/price-lists', [Pr::class, 'storePlan'])->middleware('can:Manage pricing rules')->name('price-list.store');
    Route::get('/price-lists/{plan}', [Pr::class, 'plan'])->middleware('can:Manage pricing rules')->whereNumber('plan')->name('price-list');
    Route::post('/price-lists/{plan}/rules', [Pr::class, 'storeRule'])->middleware('can:Manage pricing rules')->whereNumber('plan')->name('price-list.rule');
    Route::delete('/price-lists/{plan}/rules/{rule}', [Pr::class, 'deleteRule'])->middleware('can:Manage pricing rules')->whereNumber(['plan', 'rule'])->name('price-list.rule.delete');
    Route::post('/price-lists/{plan}/status', [Pr::class, 'setPlanStatus'])->middleware('can:Manage pricing rules')->whereNumber('plan')->name('price-list.status');

    Route::get('/merchants', [Mc::class, 'index'])->middleware('can:View vendor')->name('merchants');
    Route::post('/merchants', [Mc::class, 'store'])->middleware('can:Create vendor')->name('merchant.store');
    Route::get('/merchants/{merchant}', [Mc::class, 'show'])->middleware('can:View vendor')->whereNumber('merchant')->name('merchant');
    Route::put('/merchants/{merchant}', [Mc::class, 'update'])->middleware('can:Update vendor')->whereNumber('merchant')->name('merchant.update');
    Route::post('/merchants/{merchant}/owner', [Mc::class, 'setOwner'])->middleware('can:Update vendor')->whereNumber('merchant')->name('merchant.owner');
    Route::post('/merchants/{merchant}/live-requests/{req}', [Mc::class, 'decideLive'])->middleware('can:Update vendor')->whereNumber(['merchant', 'req'])->name('merchant.live.decide');
    Route::post('/merchants/{merchant}/keys', [Mc::class, 'issueKey'])->middleware('can:Update vendor')->whereNumber('merchant')->name('merchant.key.issue');
    Route::delete('/merchants/{merchant}/keys/{client}', [Mc::class, 'revokeKey'])->middleware('can:Update vendor')->whereNumber(['merchant', 'client'])->name('merchant.key.revoke');
    Route::post('/merchants/{merchant}/webhooks', [Mc::class, 'addWebhook'])->middleware('can:Update vendor')->whereNumber('merchant')->name('merchant.hook.add');
    Route::post('/merchants/{merchant}/webhooks/{hook}/toggle', [Mc::class, 'toggleWebhook'])->middleware('can:Update vendor')->whereNumber(['merchant', 'hook'])->name('merchant.hook.toggle');
});
