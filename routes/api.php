<?php

use App\Http\Controllers\Api\DeviceAttendanceController;
use App\Http\Controllers\Api\TimetableApiController;
use App\Http\Controllers\TimetableController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Laravel automatically prefixes every route here with /api.
| All routes below therefore live under /api/timetable/... etc.
*/

// =========================================================================
// Teacher / Student / Parent-facing timetable endpoints (mobile app)
// =========================================================================
Route::middleware('auth:sanctum')->prefix('timetable')->group(function () {

    // Teacher
    Route::get('my-timetable', [TimetableApiController::class, 'getMyTimetable']);
    Route::get('today-schedule', [TimetableApiController::class, 'getTodaySchedule']);
    Route::get('upcoming-classes', [TimetableApiController::class, 'getUpcomingClasses']);
    Route::post('mark-attendance', [TimetableApiController::class, 'markAttendance']);

    // Student / Parent
    Route::get('class-timetable/{classId}', [TimetableApiController::class, 'getClassTimetable']);
    Route::get('child-timetable/{studentId}', [TimetableApiController::class, 'getChildTimetable']);

    // Substitute requests
    Route::post('request-substitute', [TimetableApiController::class, 'requestSubstitute']);
    Route::get('substitute-requests', [TimetableApiController::class, 'getSubstituteRequests']);

    // Notifications
    Route::get('notifications', [TimetableApiController::class, 'getNotifications']);
    Route::post('notifications/mark-read', [TimetableApiController::class, 'markNotificationsRead']);

    // Web-UI helper: teachers view uses this URL from its blade JS.
    // Lives here so it resolves as /api/timetable/available-substitutes
    // (one /api prefix from Laravel, one timetable from the group above),
    // and reuses the same controller method the web route points to.
    Route::get('available-substitutes', [TimetableController::class, 'getAvailableSubstitutes'])
        ->name('api.timetable.available-substitutes');
});

// =========================================================================
// Device -> server attendance ingestion (protected by X-Device-Key)
// =========================================================================
Route::middleware('device.auth')->post('/device/attendance', [DeviceAttendanceController::class, 'store']);

// =========================================================================
// Module feature flags — the remote control portal reads/sets 1/0 here.
// Protected by the shared key (bearer) + HMAC signature on writes.
// =========================================================================
Route::middleware('remote.portal')->prefix('feature-flags')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'index']);
    Route::get('/health', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'health']);
    Route::get('/catalog', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'catalog']);
    Route::post('/sync', [\App\Http\Controllers\Api\FeatureFlagApiController::class, 'sync']);
});