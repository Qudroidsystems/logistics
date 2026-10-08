<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| Web routes — admin / back-office portal
|--------------------------------------------------------------------------
| Domain modules (deliveries, dispatch, shopping, stores, vendors ...) register
| their own routes from app/Modules/
*/

Route::get('/', fn () => redirect('/login'));

Auth::routes(['register' => false]);
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Maintenance page is always reachable.
Route::view('/maintenance', 'errors.maintenance', ['m' => \App\Models\MaintenanceSetting::current()])->name('maintenance.page');

// CSRF refresh (used by the auto-refresh feature)
Route::get('/refresh-csrf', function () {
    if (request()->ajax()) {
        Session::regenerateToken();
        return response()->json(['csrf_token' => csrf_token()]);
    }
    abort(404);
})->name('csrf.refresh');

// Payment provider webhooks / return URL (public; CSRF-exempt in bootstrap/app.php)
Route::post('/webhook/paystack', [PaymentWebhookController::class, 'paystack'])->name('webhook.paystack');
Route::post('/webhook/opay', [PaymentWebhookController::class, 'opay'])->name('webhook.opay');
Route::get('/payment/callback', [PaymentWebhookController::class, 'callback'])->name('payments.callback');

Route::middleware('auth')->group(function () {

    // Forced first-login password change
    Route::get('/password/change', [ChangePasswordController::class, 'form'])->name('password.change');
    Route::post('/password/change', [ChangePasswordController::class, 'update'])->name('password.change.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ------------------------------------------------------------ users, roles, permissions
    Route::get('/users/roles', [UserController::class, 'roles'])->name('users.roles');
    Route::get('/user/overview/{id}', [UserController::class, 'show'])->name('users.overview');
    Route::post('/users/{id}/toggle-disabled', [UserController::class, 'toggleDisabled'])->name('users.toggle-disabled');
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::resource('users', UserController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::get('/profile/{id}/settings', [UserController::class, 'settings'])->name('profile.settings');
    Route::put('/profile/{id}', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/{id}/password', [UserController::class, 'updatePassword'])->name('profile.password');

    Route::post('roles/bulk-remove-users', [RoleController::class, 'bulkRemoveUsers'])->name('roles.bulkremoveusers');
    Route::get('/roles/{role}/users', [RoleController::class, 'getRoleUsers'])->name('roles.users');
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    Route::get('/adduser/{id}', [RoleController::class, 'adduser'])->name('roles.adduser');
    Route::post('/updateuserrole', [RoleController::class, 'updateuserrole'])->name('roles.updateuserrole');
    Route::delete('roles/removeuserrole/{userid}/{roleid}', [RoleController::class, 'removeuserrole'])->name('roles.removeuserrole');

    // ------------------------------------------------------------ notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');

    // ------------------------------------------------------------ audit / activity
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity.index');
    Route::get('/activity-log/export', [ActivityLogController::class, 'export'])->name('activity.export');
    Route::get('/online-staff', [ActivityLogController::class, 'online'])->name('online-staff.index');
    Route::get('/online-staff/count', [ActivityLogController::class, 'onlineCount'])->name('online-staff.count');

    // ------------------------------------------------------------ platform settings
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::prefix('backups')->name('backups.')->group(function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');
            Route::post('/run', [BackupController::class, 'run'])->name('run');
            Route::post('/settings', [BackupController::class, 'saveSettings'])->name('settings');
            Route::get('/{backup}/download', [BackupController::class, 'download'])->whereNumber('backup')->name('download');
            Route::delete('/{backup}', [BackupController::class, 'destroy'])->whereNumber('backup')->name('destroy');
        });

        Route::prefix('payment-gateways')->name('payment-gateways.')->group(function () {
            Route::get('/', [PaymentGatewayController::class, 'index'])->name('index');
            Route::post('/{gateway}/toggle', [PaymentGatewayController::class, 'toggleGateway'])->name('toggle');
            Route::put('/{gateway}', [PaymentGatewayController::class, 'updateConfig'])->name('update');
            Route::post('/test/{gateway}', [PaymentGatewayController::class, 'testGateway'])->name('test');
        });
    });

    Route::get('/admin/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.settings');
    Route::post('/admin/maintenance', [MaintenanceController::class, 'save'])->name('maintenance.save');

    Route::get('/admin/feature-flags', [FeatureFlagController::class, 'index'])->name('feature-flags.index');
    Route::post('/admin/feature-flags/sync-settings', [FeatureFlagController::class, 'saveSync'])->name('feature-flags.sync-settings');
    Route::post('/admin/feature-flags/regenerate-key', [FeatureFlagController::class, 'regenerateKey'])->name('feature-flags.regenerate-key');
    Route::post('/admin/feature-flags/pull', [FeatureFlagController::class, 'pullNow'])->name('feature-flags.pull');
    Route::post('/admin/feature-flags/{flag}/toggle', [FeatureFlagController::class, 'toggle'])->whereNumber('flag')->name('feature-flags.toggle');
    Route::post('/admin/feature-flags/{flag}/control', [FeatureFlagController::class, 'setControl'])->whereNumber('flag')->name('feature-flags.control');
});

// Staff console for the delivery marketplace
require __DIR__.'/ops.php';
require __DIR__.'/provider.php';
require __DIR__.'/account.php';
require __DIR__.'/driver.php';
