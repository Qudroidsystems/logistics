<?php

use App\Http\Controllers\Provider\RequestsController as R;
use App\Http\Controllers\Provider\SetupController as S;
use App\Http\Controllers\Provider\WorkspaceController as W;
use Illuminate\Support\Facades\Route;

/** The provider's own workspace (companies, riders and shoppers). Roles are checked inside the controller. */
Route::middleware('auth')->prefix('provider')->name('provider.')->group(function () {
    Route::get('/', [W::class, 'dashboard'])->name('dashboard');

    Route::get('/dispatch', [W::class, 'board'])->name('dispatch');
    Route::get('/jobs', [W::class, 'jobs'])->name('jobs');
    Route::get('/jobs/{shipment}', [W::class, 'job'])->name('job');
    Route::post('/jobs/{shipment}/assign', [W::class, 'assignJob'])->name('job.assign');
    Route::post('/jobs/{shipment}/cancel', [W::class, 'cancelJob'])->name('job.cancel');

    // Customer requests: inbox, offers and negotiation
    Route::get('/requests', [R::class, 'index'])->name('requests');
    Route::get('/requests/{serviceRequest}', [R::class, 'show'])->name('request');
    Route::post('/requests/{serviceRequest}/offer', [R::class, 'offer'])->name('request.offer');
    Route::get('/negotiations/{thread}', [R::class, 'thread'])->name('thread');
    Route::post('/negotiations/{thread}/say', [R::class, 'say'])->name('thread.say');
    Route::post('/negotiations/{thread}/counter', [R::class, 'counter'])->name('thread.counter');
    Route::post('/negotiations/{thread}/accept', [R::class, 'accept'])->name('thread.accept');

    Route::get('/wallet', [W::class, 'wallet'])->name('wallet');
    Route::post('/wallet/payout', [W::class, 'requestPayout'])->name('payout');
    Route::post('/wallet/bank', [W::class, 'addBank'])->name('bank');

    Route::get('/team', [W::class, 'team'])->name('team');
    Route::post('/team/invite', [W::class, 'invite'])->name('team.invite');
    Route::delete('/team/invitations/{invitation}', [W::class, 'revokeInvite'])->name('team.revoke');
    Route::put('/team/members/{user}/role', [W::class, 'changeRole'])->whereNumber('user')->name('team.role');
    Route::delete('/team/members/{user}', [W::class, 'removeMember'])->whereNumber('user')->name('team.remove');
    Route::put('/team/members/{user}/driver-status', [W::class, 'driverStatus'])->whereNumber('user')->name('team.driver');
    Route::put('/team/members/{user}/driver-pay', [W::class, 'driverPay'])->whereNumber('user')->name('team.pay');
    Route::put('/team/members/{user}/vehicle', [W::class, 'assignVehicle'])->whereNumber('user')->name('team.vehicle');

    // Becoming a provider and getting the account ready
    Route::get('/start', [S::class, 'start'])->middleware('marketplace')->name('start');
    Route::post('/start', [S::class, 'register'])->middleware(['marketplace', 'throttle:10,1'])->name('register');
    Route::get('/setup', [S::class, 'onboarding'])->name('onboarding');
    Route::post('/setup/profile', [S::class, 'saveProfile'])->name('profile.save');
    Route::post('/setup/availability', [S::class, 'availability'])->name('availability');
    Route::post('/setup/documents', [S::class, 'uploadDocument'])->middleware('throttle:20,1')->name('document.upload');
    Route::post('/setup/vehicles', [S::class, 'addVehicle'])->name('vehicle.add');
    Route::post('/setup/rate-cards', [S::class, 'saveRateCard'])->name('ratecard.save');
    Route::delete('/setup/rate-cards/{card}', [S::class, 'deleteRateCard'])->whereNumber('card')->name('ratecard.delete');
    Route::post('/setup/areas', [S::class, 'saveAreas'])->name('areas.save');
    Route::post('/setup/submit', [S::class, 'submit'])->name('submit');
});
