<?php

use App\Http\Controllers\Api\FeatureFlagApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes (prefixed /api by Laravel)
|--------------------------------------------------------------------------
| Versioned public API for the customer app, driver app, vendor/store portals
| and business integrations lives under /api/v1 (Sanctum token auth). Each
| domain module registers its own v1 routes from app/Modules/*/routes/api.php.
*/

Route::prefix('v1')->group(function () {
    Route::get('/ping', fn () => response()->json(['ok' => true, 'service' => config('app.name'), 'time' => now()->toIso8601String()]));
});

// Module feature flags — the remote control portal reads/sets 1/0 here.
Route::middleware('remote.portal')->prefix('feature-flags')->group(function () {
    Route::get('/', [FeatureFlagApiController::class, 'index']);
    Route::get('/health', [FeatureFlagApiController::class, 'health']);
    Route::get('/catalog', [FeatureFlagApiController::class, 'catalog']);
    Route::post('/sync', [FeatureFlagApiController::class, 'sync']);
});

// Module routes: each app/Modules/<Name>/routes/api.php declares its own prefix and middleware.
foreach (glob(app_path('Modules/*/routes/api.php')) ?: [] as $moduleRoutes) {
    require $moduleRoutes;
}
