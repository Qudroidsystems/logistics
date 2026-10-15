<?php

use App\Http\Controllers\Provider\AlertsController as A;
use App\Http\Controllers\Provider\BackofficeController as Bo;
use App\Http\Controllers\Provider\PickupPointsController as Pp;
use App\Http\Controllers\Provider\PricingController as P;
use App\Http\Controllers\Provider\RequestsController as R;
use App\Http\Controllers\Provider\SetupController as S;
use App\Http\Controllers\Provider\VerificationController as V;
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
    Route::get('/jobs/{shipment}/edit', [Bo::class, 'editJob'])->name('job.edit');
    Route::put('/jobs/{shipment}', [Bo::class, 'updateJob'])->name('job.update');
    Route::get('/bookings/new', [Bo::class, 'newBooking'])->name('booking.new');
    Route::get('/bookings/bulk', [Bo::class, 'bulk'])->name('bulk');
    Route::get('/bookings/bulk/template.csv', [Bo::class, 'bulkTemplate'])->name('bulk.template');
    Route::post('/bookings/bulk', [Bo::class, 'bulkCheck'])->middleware('throttle:10,1')->name('bulk.check');
    Route::get('/bookings/bulk/{batch}', [Bo::class, 'bulkShow'])->name('bulk.show');
    Route::post('/bookings/bulk/{batch}/book', [Bo::class, 'bulkBook'])->middleware('throttle:10,1')->name('bulk.book');
    Route::post('/bookings/bulk/{batch}/discard', [Bo::class, 'bulkDiscard'])->name('bulk.discard');
    Route::post('/bookings/price', [Bo::class, 'price'])->middleware('throttle:30,1')->name('booking.price');
    Route::post('/bookings', [Bo::class, 'storeBooking'])->middleware('throttle:20,1')->name('booking.store');
    Route::get('/reports/{report}/export.{format}', [Bo::class, 'export'])->whereIn('format', ['xlsx', 'pdf'])->name('reports.export');
    Route::get('/reports/{report?}', [Bo::class, 'reports'])->name('reports');
    Route::get('/jobs/{shipment}/labels', [P::class, 'labels'])->name('job.labels');
    Route::post('/jobs/{shipment}/exceptions/{exception}', [W::class, 'decideException'])->name('job.exception');
    Route::post('/jobs/{shipment}/return', [Pp::class, 'bookReturn'])->middleware('throttle:20,1')->name('job.return');

    // Pickup points: where parcels wait for the receiver, and the counter where they collect
    Route::get('/pickup-points', [Pp::class, 'index'])->name('points');
    Route::post('/pickup-points', [Pp::class, 'store'])->name('points.store');
    Route::put('/pickup-points/{point}', [Pp::class, 'store'])->name('points.update');
    Route::post('/pickup-points/{point}/status', [Pp::class, 'status'])->name('points.status');
    Route::post('/pickup-points/parcels/{shipment}/collect', [Pp::class, 'collect'])->middleware('throttle:30,1')->name('points.collect');
    Route::post('/pickup-points/parcels/{shipment}/not-collected', [Pp::class, 'notCollected'])->name('points.not_collected');

    // Proof of delivery: the provider's standard checks per service type
    Route::get('/verification', [V::class, 'index'])->name('verification');
    Route::post('/verification', [V::class, 'save'])->name('verification.save');
    Route::delete('/verification/{serviceType}', [V::class, 'remove'])->whereNumber('serviceType')->name('verification.remove');

    // Arrival alerts: when the job giver and receiver hear the rider is close
    Route::get('/alerts', [A::class, 'index'])->name('alerts');
    Route::post('/alerts', [A::class, 'save'])->name('alerts.save');

    // Pricing calculator: cost profiles and estimates
    Route::get('/pricing', [P::class, 'index'])->name('pricing');
    Route::post('/pricing/profiles', [P::class, 'save'])->name('pricing.save');
    Route::put('/pricing/profiles/{profile}', [P::class, 'save'])->name('pricing.update');
    Route::delete('/pricing/profiles/{profile}', [P::class, 'retire'])->name('pricing.retire');
    Route::post('/pricing/estimate', [P::class, 'estimate'])->middleware('throttle:30,1')->name('pricing.estimate');

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
