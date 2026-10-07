<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:10,1');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/password', [AuthController::class, 'changePassword']);
        Route::post('/email/send-code', [EmailVerificationController::class, 'send']);
        Route::post('/email/verify', [EmailVerificationController::class, 'verify'])->middleware('throttle:10,1');
    });
});
