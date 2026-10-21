<?php

use App\Http\Controllers\Api\Provider\DriverAssignmentController;
use App\Http\Controllers\Api\Provider\FleetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1/provider')->group(function () {
    Route::get('/drivers', [DriverAssignmentController::class, 'drivers']);
    Route::post('/shipments/{shipment}/assign', [DriverAssignmentController::class, 'assign']);
    // Live fleet: every driver's position and state, and one driver's path (?minutes=60).
    Route::get('/fleet', [FleetController::class, 'index'])->middleware('throttle:120,1');
    Route::get('/fleet/drivers/{driver}/trail', [FleetController::class, 'trail'])->where('driver', '[A-Za-z0-9]{26}');
});
