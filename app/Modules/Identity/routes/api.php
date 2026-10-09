<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\PhoneVerificationController;
use App\Http\Controllers\Api\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::get('v1/app-config', [\App\Http\Controllers\Api\AppConfigController::class, 'show'])->middleware('throttle:60,1');

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
        Route::post('/phone', [PhoneVerificationController::class, 'set'])->middleware('throttle:10,1');
        Route::post('/phone/send-code', [PhoneVerificationController::class, 'send']);
        Route::post('/phone/verify', [PhoneVerificationController::class, 'verify'])->middleware('throttle:10,1');
    });
});
