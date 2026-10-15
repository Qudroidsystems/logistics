<?php

use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Provider\TeamSecurityController;
use Illuminate\Support\Facades\Route;

/** Two-step sign-in, sign-in rules and the data-change audit trail. */

// The code screen between the password and being signed in.
Route::middleware('guest')->group(function () {
    Route::get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor/challenge', [TwoFactorController::class, 'verify'])->middleware('throttle:10,1')->name('two-factor.verify');
});

Route::middleware('auth')->group(function () {
    // Everyone's own page.
    Route::get('/security', [TwoFactorController::class, 'show'])->name('security.index');
    Route::post('/security/two-factor', [TwoFactorController::class, 'start'])->name('security.two-factor.start');
    Route::post('/security/two-factor/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:10,1')->name('security.two-factor.confirm');
    Route::delete('/security/two-factor', [TwoFactorController::class, 'disable'])->middleware('throttle:10,1')->name('security.two-factor.disable');
    Route::post('/security/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->middleware('throttle:10,1')->name('security.recovery-codes');

    // Staff: sign-in rules and resets.
    Route::get('/admin/security', [SecurityController::class, 'index'])->name('admin.security.index');
    Route::post('/admin/security', [SecurityController::class, 'save'])->name('admin.security.save');
    Route::post('/admin/security/users/{user}/reset', [SecurityController::class, 'reset'])->whereNumber('user')->name('admin.security.reset');

    // Staff: who changed what.
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('/audit-log/export', [AuditLogController::class, 'export'])->name('audit.export');

    // Provider owners: require it for their own team.
    Route::post('/provider/team/security', [TeamSecurityController::class, 'save'])->name('provider.team.security');
});
