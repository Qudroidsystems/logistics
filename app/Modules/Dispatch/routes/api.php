<?php

use App\Http\Controllers\Api\Provider\DriverAssignmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1/provider')->group(function () {
    Route::get('/drivers', [DriverAssignmentController::class, 'drivers']);
    Route::post('/shipments/{shipment}/assign', [DriverAssignmentController::class, 'assign']);
});
