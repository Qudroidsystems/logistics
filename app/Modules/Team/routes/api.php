<?php

use App\Http\Controllers\Api\Provider\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::prefix('provider/team')->group(function () {
        Route::get('/', [TeamController::class, 'index']);
        Route::post('/invitations', [TeamController::class, 'invite'])->middleware('throttle:20,1');
        Route::delete('/invitations/{invitation}', [TeamController::class, 'revoke']);
        Route::put('/members/{user}/role', [TeamController::class, 'changeRole'])->whereNumber('user');
        Route::delete('/members/{user}', [TeamController::class, 'remove'])->whereNumber('user');
        Route::put('/members/{user}/driver-status', [TeamController::class, 'driverStatus'])->whereNumber('user');
        Route::put('/members/{user}/vehicle', [TeamController::class, 'assignVehicle'])->whereNumber('user');
    });

    // For the person being invited.
    Route::get('/team/invitations', [TeamController::class, 'myInvitations']);
    Route::post('/team/invitations/accept', [TeamController::class, 'accept'])->middleware('throttle:10,1');
});
