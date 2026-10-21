<?php

use App\Http\Controllers\Customer\AccountController as A;
use App\Http\Controllers\Customer\BookingPagesController as Bk;
use Illuminate\Support\Facades\Route;

/** The customer's own area: requests and offers, paying, orders, wallet. Every query is scoped to the signed-in user. */
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [A::class, 'dashboard'])->name('dashboard');
    Route::get('/me', [\App\Http\Controllers\MeController::class, 'show'])->name('me');
    Route::post('/me', [\App\Http\Controllers\MeController::class, 'profile'])->middleware('throttle:20,1')->name('me.profile');
    Route::post('/me/password', [\App\Http\Controllers\MeController::class, 'password'])->middleware('throttle:6,1')->name('me.password');
    Route::get('/phone', [\App\Http\Controllers\PhonePageController::class, 'show'])->name('phone');
    Route::post('/phone', [\App\Http\Controllers\PhonePageController::class, 'save'])->middleware('throttle:10,1')->name('phone.save');
    Route::post('/phone/send', [\App\Http\Controllers\PhonePageController::class, 'send'])->middleware('throttle:6,1')->name('phone.send');
    Route::post('/phone/verify', [\App\Http\Controllers\PhonePageController::class, 'verify'])->middleware('throttle:10,1')->name('phone.verify');

    // Joining a company's team (drivers, dispatchers ...) from an emailed invitation.
    Route::get('/invitations', [\App\Http\Controllers\Customer\InvitationsController::class, 'index'])->name('invitations');
    Route::post('/invitations/accept', [\App\Http\Controllers\Customer\InvitationsController::class, 'accept'])->middleware('throttle:10,1')->name('invitations.accept');

    Route::get('/providers', [A::class, 'providers'])->middleware('marketplace')->name('providers');
    Route::get('/requests', [A::class, 'requests'])->name('requests');
    Route::get('/requests/new', [A::class, 'newRequest'])->name('request.new');
    Route::post('/requests', [A::class, 'createRequest'])->middleware('throttle:20,1')->name('request.create');
    Route::get('/requests/{serviceRequest}', [A::class, 'request'])->name('request');

    Route::get('/negotiations/{thread}', [A::class, 'thread'])->name('thread');
    Route::post('/negotiations/{thread}/message', [A::class, 'say'])->middleware('throttle:30,1')->name('thread.say');
    Route::post('/negotiations/{thread}/counter', [A::class, 'counter'])->name('thread.counter');
    Route::post('/negotiations/{thread}/accept', [A::class, 'accept'])->name('thread.accept');
    Route::post('/negotiations/{thread}/reject', [A::class, 'reject'])->name('thread.reject');

    Route::get('/pay/{agreement}', [A::class, 'pay'])->name('pay');
    Route::post('/pay/{agreement}', [A::class, 'doPay'])->middleware('throttle:10,1')->name('pay.do');

    Route::get('/orders', [A::class, 'orders'])->name('orders');
    Route::get('/orders/{shipment}', [A::class, 'order'])->name('order');
    Route::post('/orders/{shipment}/cancel', [A::class, 'cancel'])->name('order.cancel');
    Route::post('/orders/{shipment}/new-code', [A::class, 'newCode'])->middleware('throttle:6,60')->name('order.code');
    Route::post('/orders/{shipment}/confirm', [A::class, 'confirm'])->name('order.confirm');
    Route::post('/orders/{shipment}/object', [A::class, 'object'])->name('order.object');
    Route::post('/orders/{shipment}/rate', [A::class, 'rate'])->name('order.rate');

    Route::get('/addresses', [Bk::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [Bk::class, 'saveAddress'])->middleware('throttle:20,1')->name('address.save');
    Route::delete('/addresses/{address}', [Bk::class, 'deleteAddress'])->name('address.delete');
    Route::post('/requests/{serviceRequest}/files', [Bk::class, 'upload'])->middleware('throttle:20,1')->name('request.file');
    Route::delete('/files/{attachment}', [Bk::class, 'deleteFile'])->name('file.delete');
    Route::post('/pay/{agreement}/promo', [Bk::class, 'applyPromo'])->middleware('throttle:10,1')->name('pay.promo');
    Route::delete('/pay/{agreement}/promo', [Bk::class, 'removePromo'])->name('pay.promo.remove');
    Route::post('/pay/{agreement}/points', [Bk::class, 'usePoints'])->name('pay.points');
    Route::post('/pay/{agreement}/gift-card', [Bk::class, 'applyGiftCard'])->middleware('throttle:10,1')->name('pay.giftcard');
    Route::delete('/pay/{agreement}/gift-card', [Bk::class, 'removeGiftCard'])->name('pay.giftcard.remove');
    Route::get('/orders/{shipment}/receipt', [Bk::class, 'receipt'])->middleware('throttle:20,1')->name('order.receipt');

    Route::get('/book', [\App\Http\Controllers\Customer\BusinessBookingController::class, 'book'])->name('book');
    Route::get('/recurring', [\App\Http\Controllers\Customer\BusinessBookingController::class, 'recurring'])->name('recurring');
    Route::post('/recurring', [\App\Http\Controllers\Customer\BusinessBookingController::class, 'store'])->middleware('throttle:20,1')->name('recurring.store');
    Route::post('/recurring/{schedule}/status', [\App\Http\Controllers\Customer\BusinessBookingController::class, 'status'])->where('schedule', '[A-Za-z0-9]{26}')->name('recurring.status');
    Route::get('/plan', [\App\Http\Controllers\Customer\PlanController::class, 'show'])->name('plan');
    Route::post('/plan', [\App\Http\Controllers\Customer\PlanController::class, 'subscribe'])->middleware('throttle:10,1')->name('plan.subscribe');
    Route::post('/plan/change', [\App\Http\Controllers\Customer\PlanController::class, 'change'])->middleware('throttle:10,1')->name('plan.change');
    Route::post('/plan/cancel', [\App\Http\Controllers\Customer\PlanController::class, 'cancel'])->name('plan.cancel');
    Route::post('/plan/resume', [\App\Http\Controllers\Customer\PlanController::class, 'resume'])->name('plan.resume');
    Route::post('/plan/pay', [\App\Http\Controllers\Customer\PlanController::class, 'payNow'])->middleware('throttle:10,1')->name('plan.pay');
    Route::get('/wallet', [A::class, 'walletPage'])->name('wallet');
    Route::post('/wallet/top-up', [A::class, 'topUp'])->middleware('throttle:10,1')->name('wallet.topup');
    Route::post('/wallet/bank', [A::class, 'addBank'])->name('wallet.bank');
    Route::post('/wallet/withdraw', [A::class, 'withdraw'])->middleware('throttle:10,1')->name('wallet.withdraw');
});
