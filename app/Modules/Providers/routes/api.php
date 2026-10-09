<?php

use App\Http\Controllers\Api\Admin\AdminProviderController;
use App\Http\Controllers\Api\Provider\ProviderOnboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::prefix('provider')->group(function () {
        Route::get('/catalog', [ProviderOnboardingController::class, 'catalog']);
        Route::post('/register', [ProviderOnboardingController::class, 'register'])->middleware('marketplace');

        Route::get('/onboarding', [ProviderOnboardingController::class, 'onboarding']);
        Route::get('/profile', [ProviderOnboardingController::class, 'profile']);
        Route::put('/profile', [ProviderOnboardingController::class, 'updateProfile']);
        Route::put('/availability', [ProviderOnboardingController::class, 'availability']);

        Route::get('/documents', [ProviderOnboardingController::class, 'documents']);
        Route::post('/documents', [ProviderOnboardingController::class, 'uploadDocument'])->middleware('throttle:20,1');

        Route::get('/vehicles', [ProviderOnboardingController::class, 'vehicles']);
        Route::post('/vehicles', [ProviderOnboardingController::class, 'addVehicle']);

        Route::get('/rate-cards', [ProviderOnboardingController::class, 'rateCards']);
        Route::post('/rate-cards', [ProviderOnboardingController::class, 'saveRateCard']);
        Route::put('/rate-cards/{card}', [ProviderOnboardingController::class, 'saveRateCard'])->whereNumber('card');
        Route::delete('/rate-cards/{card}', [ProviderOnboardingController::class, 'deleteRateCard'])->whereNumber('card');

        Route::get('/zones', [ProviderOnboardingController::class, 'zones']);
        Route::put('/service-areas', [ProviderOnboardingController::class, 'setServiceAreas']);

        Route::post('/submit', [ProviderOnboardingController::class, 'submit']);
    });

    Route::prefix('admin')->middleware('marketplace')->group(function () {
        Route::get('/provider-applications', [AdminProviderController::class, 'applications'])->middleware('permission:View kyc');
        Route::get('/provider-applications/{operator}', [AdminProviderController::class, 'application'])->middleware('permission:View kyc');
        Route::get('/kyc-documents/{doc}/file', [AdminProviderController::class, 'document'])->middleware('permission:View kyc');
        Route::post('/kyc-documents/{doc}/approve', [AdminProviderController::class, 'approveDocument'])->middleware('permission:Approve kyc');
        Route::post('/kyc-documents/{doc}/reject', [AdminProviderController::class, 'rejectDocument'])->middleware('permission:Reject kyc');

        Route::post('/provider-applications/{operator}/approve', [AdminProviderController::class, 'approve'])->middleware('permission:Approve kyc');
        Route::post('/provider-applications/{operator}/request-changes', [AdminProviderController::class, 'requestChanges'])->middleware('permission:Reject kyc');
        Route::post('/provider-applications/{operator}/reject', [AdminProviderController::class, 'reject'])->middleware('permission:Reject kyc');

        Route::post('/providers/{operator}/suspend', [AdminProviderController::class, 'suspend'])->middleware('permission:Suspend vendor|Suspend driver|Suspend shopper');
        Route::post('/providers/{operator}/reinstate', [AdminProviderController::class, 'reinstate'])->middleware('permission:Suspend vendor|Suspend driver|Suspend shopper');
    });
});
