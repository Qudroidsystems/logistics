<?php

use \App\Http\Controllers\SchoolInformationController;
use App\Http\Controllers\Admin\AdminScoreEntryController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\ScholarshipController;
use App\Http\Controllers\Admin\SiblingGroupController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\Api\DeviceAttendanceController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceSettingController;
use App\Http\Controllers\BiodataController;
use App\Http\Controllers\BroadsheetController;
use App\Http\Controllers\CBTController;
use App\Http\Controllers\ClassBroadsheetController;
use App\Http\Controllers\ClasscategoryController;
use App\Http\Controllers\ClassOperationController;
use App\Http\Controllers\ClassTeacherController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\CompulsorySubjectClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceUserMappingController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamPauseController;
use App\Http\Controllers\ExamTimetableController;
use App\Http\Controllers\Finance\PayrollController;
use App\Http\Controllers\Finance\StaffPaymentController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobStatusController;
use App\Http\Controllers\LiveAttendanceController;
use App\Http\Controllers\MockSubjectVettingController;
use App\Http\Controllers\MyClassController;
use App\Http\Controllers\MyMockSubjectVettingsController;
use App\Http\Controllers\MyPrincipalsCommentController;
use App\Http\Controllers\MyresultroomController;
use App\Http\Controllers\MyScoreSheetController;
use App\Http\Controllers\MySubjectController;
use App\Http\Controllers\MySubjectVettingsController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\Payment\EnhancedSchoolPaymentController;
use App\Http\Controllers\Payment\FlexibleOnlinePaymentController;
use App\Http\Controllers\Payment\OnlinePaymentController;
use App\Http\Controllers\Payment\OnlineFeeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PrincipalsCommentController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PromotionRuleTemplateController;
use App\Http\Controllers\PromotionSettingController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\Reports\AnalysisReportController;
use App\Http\Controllers\Reports\FinancialReportController;
use App\Http\Controllers\Reports\ReminderController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SchoolArmController;
use App\Http\Controllers\SchoolBillController;
use App\Http\Controllers\SchoolBillTermSessionController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolHouseController;
use App\Http\Controllers\SchoolPaymentController;
use App\Http\Controllers\SchoolsessionController;
use App\Http\Controllers\SchooltermController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffImageUploadController;
use App\Http\Controllers\StudentAssessmentController;
use App\Http\Controllers\StudentClassOperationsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentHouseController;
use App\Http\Controllers\StudentIdCardController;
use App\Http\Controllers\StudentImageUploadController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\ResultAccessController;
use App\Http\Controllers\SchoolNoticeController;
use App\Http\Controllers\MessagingSettingsController;
use App\Http\Controllers\ResultSendController;
use App\Http\Controllers\ResultLinkController;
use App\Http\Controllers\ResultVerifyController;
use App\Http\Controllers\ParentContactController;
use App\Http\Controllers\AutoMessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StudentpersonalityprofileController;
use App\Http\Controllers\StudentResultsController;
use App\Http\Controllers\SubjectClassController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubjectOperationController;
use App\Http\Controllers\SubjectTeacherController;
use App\Http\Controllers\SubjectVettingController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableReportController;
use App\Http\Controllers\TranscriptController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViewStudentController;
use App\Http\Controllers\ViewStudentMockReportController;
use App\Http\Controllers\ViewStudentReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root & public utility routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/test-sibling-data/{id}', function ($id) {
    $group = DB::table('sibling_groups')->where('id', $id)->first();
    $students = DB::table('sibling_group_students')
        ->where('sibling_group_id', $id)
        ->join('studentRegistration', 'sibling_group_students.student_id', '=', 'studentRegistration.id')
        ->select('studentRegistration.id', 'studentRegistration.firstname', 'studentRegistration.lastname', 'studentRegistration.admissionNo')
        ->get();

    return response()->json([
        'group'         => $group,
        'students'      => $students,
        'student_count' => $students->count(),
    ]);
});

Auth::routes();
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Maintenance mode: public info page (always reachable) + admin settings (authed, permission-gated)
Route::view('/maintenance', 'errors.maintenance', ['m' => \App\Models\MaintenanceSetting::current()])->name('maintenance.page');
Route::middleware('auth')->group(function () {
    Route::get('/admin/maintenance', [\App\Http\Controllers\Admin\MaintenanceController::class, 'index'])->name('maintenance.settings');
    Route::post('/admin/maintenance', [\App\Http\Controllers\Admin\MaintenanceController::class, 'save'])->name('maintenance.save');

    // Module access (feature flags)
    Route::get('/admin/feature-flags', [\App\Http\Controllers\Admin\FeatureFlagController::class, 'index'])->name('feature-flags.index');
    Route::post('/admin/feature-flags/sync-settings', [\App\Http\Controllers\Admin\FeatureFlagController::class, 'saveSync'])->name('feature-flags.sync-settings');
    Route::post('/admin/feature-flags/regenerate-key', [\App\Http\Controllers\Admin\FeatureFlagController::class, 'regenerateKey'])->name('feature-flags.regenerate-key');
    Route::post('/admin/feature-flags/pull', [\App\Http\Controllers\Admin\FeatureFlagController::class, 'pullNow'])->name('feature-flags.pull');
    Route::post('/admin/feature-flags/{flag}/toggle', [\App\Http\Controllers\Admin\FeatureFlagController::class, 'toggle'])->whereNumber('flag')->name('feature-flags.toggle');
    Route::post('/admin/feature-flags/{flag}/control', [\App\Http\Controllers\Admin\FeatureFlagController::class, 'setControl'])->whereNumber('flag')->name('feature-flags.control');
});

// CSRF refresh (used by the auto-refresh feature)
Route::get('/refresh-csrf', function () {
    if (request()->ajax()) {
        Session::regenerateToken();
        return response()->json(['csrf_token' => csrf_token()]);
    }
    return abort(404);
})->middleware('web')->name('refresh.csrf');

// Public ID card verification
Route::get('/student-id-cards/verify/{token}', [StudentIdCardController::class, 'verify'])
    ->name('student-id-cards.verify');

// Test/session helper routes — outside auth so they can be hit without a session
Route::get('/test-session-expired', function () {
    return redirect()->route('login')
        ->with('session_expired', true)
        ->with('error', 'Your session has expired. Please login again.')
        ->with('intended', url()->previous() ?? '/dashboard');
})->name('test.session.expired');

Route::get('/force-419', function () {
    if (auth()->check()) {
        auth()->logout();
    }
    session()->flush();
    session()->regenerate();

    return redirect()->route('login')
        ->with('session_expired', true)
        ->with('error', 'Your session has expired. Please login again.')
        ->with('intended', '/dashboard');
})->name('force.419');

/*
|--------------------------------------------------------------------------
| Public / Signed Routes
|--------------------------------------------------------------------------
| ICS calendar feed and webhooks MUST sit outside the auth group so
| signed URLs and third-party callbacks work without a logged-in session.
*/

// ICS calendar feed — public, verified via signed URL
Route::get('/timetable/ics/{teacherId}', [TimetableController::class, 'exportIcs'])
    ->name('timetable.ics')
    ->middleware('signed');

// Report card download links sent to parents (no login; token + expiry)
Route::get('/r/{token}', [ResultLinkController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('results.link');

// Report card verification (QR code on the printed card; signed code, no login)
Route::get('/verify/{code}', [ResultVerifyController::class, 'show'])
    ->where('code', '[0-9a-f\-]{10,80}')->middleware('throttle:60,1')->name('results.verify');

// Public school calendar (no login) + iCal subscribe feed.
Route::get('/calendar/public', [\App\Http\Controllers\PublicCalendarController::class, 'index'])->name('calendar.public');
Route::get('/calendar/feed/{token}.ics', [\App\Http\Controllers\PublicCalendarController::class, 'ical'])
    ->where('token', '[a-f0-9]{32}')->middleware('throttle:120,1')->name('calendar.ical');

// Public certificate verification (QR scan, no login).
Route::get('/verify-certificate/{token}', [\App\Http\Controllers\PublicCertificateController::class, 'verify'])
    ->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('certificates.verify');

// Payment gateway webhooks — no CSRF, no auth
Route::prefix('webhook')->group(function () {
    // School-fee payments (online-fees). Signature-checked; CSRF is skipped for webhook/*.
    Route::post('/paystack',    [OnlineFeeController::class, 'webhook'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, \App\Http\Middleware\CustomVerifyCsrfToken::class])
        ->name('webhook.paystack');
    // OPay payment notifications (school fees). Always re-checked with OPay before posting.
    Route::post('/opay', [OnlineFeeController::class, 'opayWebhook'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, \App\Http\Middleware\CustomVerifyCsrfToken::class])
        ->middleware('throttle:120,1')
        ->name('webhook.opay');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => ['auth']], function () {

    // ===================================================================
    // USER MANAGEMENT
    // ===================================================================
    Route::get('/users/all', [UserController::class, 'allUsers'])->name('users.all');
    Route::get('/users/paginate', [UserController::class, 'paginate'])->name('users.paginate');
    Route::get('/users/get-students', [UserController::class, 'getStudents'])->name('get.students');
    Route::get('/users/add-student', [UserController::class, 'createFromStudentForm'])->name('users.add-student-form');

    Route::post('/users/store-student', [UserController::class, 'storeStudent'])->name('users.store-student');
    Route::post('/users/mass-create-students', [UserController::class, 'massCreateStudents'])->name('users.mass-create-students');
    Route::post('/users/create-from-student', [UserController::class, 'createFromStudent'])->name('users.createFromStudent');

    Route::post('/users/revoke-student-password', [UserController::class, 'revokeStudentPassword'])->name('users.revoke-student-password');
    Route::post('/users/reset-single-password/{id}', [UserController::class, 'resetSingleStudentPassword'])->name('users.reset-single-password');

    Route::post('/users/get-student-credentials', [UserController::class, 'getStudentCredentials'])->name('users.get-student-credentials');
    Route::post('/users/bulk-reprint', [UserController::class, 'bulkReprintCredentials'])->name('users.bulk-reprint');

    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/staff/template', [UserController::class, 'generateStaffTemplate'])->name('users.staff.template');
    Route::post('/users/staff/import', [UserController::class, 'importStaffUsers'])->name('users.staff.import');

    Route::resource('users', UserController::class);

    // ===================================================================
    // STUDENT ID CARDS
    // ===================================================================
    Route::get('/student-id-cards', [StudentIdCardController::class, 'index'])->name('student-id-cards.index');
    Route::get('/student-id-cards/load-students', [StudentIdCardController::class, 'loadStudents'])->name('student-id-cards.load-students');
    Route::post('/student-id-cards/preview', [StudentIdCardController::class, 'preview'])->name('student-id-cards.preview');
    Route::post('/student-id-cards/download', [StudentIdCardController::class, 'download'])->name('student-id-cards.download');

    // ===================================================================
    // ROLES & PERMISSIONS
    // ===================================================================
    Route::post('roles/bulk-remove-users', [RoleController::class, 'bulkRemoveUsers'])->name('roles.bulkremoveusers');
    Route::get('/roles/{role}/users', [RoleController::class, 'getRoleUsers'])->name('roles.users');
    Route::resource('roles', RoleController::class);

    Route::get('/user/overview/{id}', [UserController::class, 'show'])->name('users.overview');
    Route::get('/users/roles', [UserController::class, 'roles']);
    Route::resource('permissions', PermissionController::class);

    Route::get('/adduser/{id}', [RoleController::class, 'adduser'])->name('roles.adduser');
    Route::post('/updateuserrole', [RoleController::class, 'updateuserrole'])->name('roles.updateuserrole');
    Route::delete('roles/removeuserrole/{userid}/{roleid}', [RoleController::class, 'removeuserrole'])->name('roles.removeuserrole');

    // ===================================================================
    // DASHBOARD
    // ===================================================================
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/chart-data', [DashboardController::class, 'getChartData'])->name('chart-data');
        Route::get('/quick-stats', [DashboardController::class, 'getQuickStats'])->name('quick-stats');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===================================================================
    // PROFILE & BIODATA
    // ===================================================================
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/settings/{id}', [BiodataController::class, 'show'])->name('settings');

        Route::post('/update-info', [BiodataController::class, 'updateProfile'])->name('update-info');
        Route::post('/update-avatar', [BiodataController::class, 'updateAvatar'])->name('update-avatar');

        Route::post('/update-student-info', [BiodataController::class, 'updateStudentInfo'])->name('update-student-info');
        Route::post('/update-parent-info', [BiodataController::class, 'updateParentInfo'])->name('update-parent-info');

        Route::post('/update-employment-info', [BiodataController::class, 'updateEmploymentInfo'])->name('update-employment-info');
        Route::post('/add-qualification', [BiodataController::class, 'storeQualification'])->name('add-qualification');
        Route::post('/update-qualification/{id}', [BiodataController::class, 'updateQualification'])->name('update-qualification');
        Route::delete('/delete-qualification/{id}', [BiodataController::class, 'deleteQualification'])->name('delete-qualification');

        Route::post('/update-email', [BiodataController::class, 'ajaxemailupdate'])->name('update-email');
        Route::post('/update-password', [BiodataController::class, 'ajaxpasswordupdate'])->name('update-password');
    });

    // ===================================================================
    // SUBJECT MANAGEMENT
    // ===================================================================
    Route::prefix('subject')->group(function () {
        Route::get('/', [SubjectController::class, 'index'])->name('subject.index');
        Route::get('/data', [SubjectController::class, 'data'])->name('subject.data');
        Route::get('/stats', [SubjectController::class, 'stats'])->name('subject.stats');
        Route::post('/store', [SubjectController::class, 'store'])->name('subject.store');
        Route::post('/bulk-destroy', [SubjectController::class, 'deleteMultiple'])->name('subject.bulk-destroy');
        Route::post('/delete-subject', [SubjectController::class, 'deletesubject'])->name('subject.deletesubject');
        Route::put('/{id}', [SubjectController::class, 'update'])->name('subject.update');
        Route::delete('/{id}', [SubjectController::class, 'destroy'])->name('subject.destroy');
    });

     Route::prefix('subjectclass')->group(function () {
            Route::get('/data', [SubjectClassController::class, 'data'])->name('subjectclass.data');
            Route::get('/stats', [SubjectClassController::class, 'stats'])->name('subjectclass.stats');
            Route::post('/bulk-destroy', [SubjectClassController::class, 'deleteMultiple'])->name('subjectclass.bulk-destroy');
            Route::post('/delete-subjectclass', [SubjectClassController::class, 'deletesubjectclass'])->name('subjectclass.deletesubjectclass');
            Route::get('/assignments/{subjectClassId}', [SubjectClassController::class, 'assignments'])->name('subjectclass.assignments');
        });
        Route::resource('subjectclass', SubjectClassController::class);
        Route::resource('staff', StaffController::class);

        Route::prefix('subjectteacher')->group(function () {
            Route::get('/data', [SubjectTeacherController::class, 'data'])->name('subjectteacher.data');
            Route::get('/stats', [SubjectTeacherController::class, 'stats'])->name('subjectteacher.stats');
            Route::post('/bulk-destroy', [SubjectTeacherController::class, 'deleteMultiple'])->name('subjectteacher.bulk-destroy');
            Route::post('/delete-subjectteacher', [SubjectTeacherController::class, 'deletesubjectteacher'])->name('subjectteacher.deletesubjectteacher');
            Route::get('/get-subjects/{id}', [SubjectTeacherController::class, 'getSubjects'])->name('subjectteacher.get-subjects');
        });
        Route::resource('subjectteacher', SubjectTeacherController::class);


    // ===================================================================
    // CLASS TEACHER
    // ===================================================================
    Route::prefix('classteacher')->name('classteacher.')->group(function () {
        Route::get('/', [ClassTeacherController::class, 'index'])->name('index');
        Route::get('/data', [ClassTeacherController::class, 'data'])->name('data');
        Route::get('/stats', [ClassTeacherController::class, 'stats'])->name('stats');
        Route::get('/assignments/{staffId}/{termId}/{sessionId}', [ClassTeacherController::class, 'assignments'])->name('assignments');
        Route::get('/{id}', [ClassTeacherController::class, 'show'])->name('show');
        Route::post('/', [ClassTeacherController::class, 'store'])->name('store');
        Route::put('/{id}', [ClassTeacherController::class, 'update'])->name('update');
        Route::delete('/{id}', [ClassTeacherController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-destroy', [ClassTeacherController::class, 'deleteMultiple'])->name('bulk-destroy');
    });

    // ===================================================================
    // SESSIONS & TERMS
    // ===================================================================
    Route::resource('session', SchoolsessionController::class);
    Route::get('/sessionid/{sessionid}', [SchoolsessionController::class, 'deletesession'])->name('session.deletesession');
    Route::post('updatesessionid', [SchoolsessionController::class, 'updatesession'])->name('session.updatesession');

    Route::resource('term', SchooltermController::class);
    Route::patch('term/{term}/status', [SchooltermController::class, 'updateStatus'])->name('term.status.update');
    Route::patch('term/{term}/promotional', [SchooltermController::class, 'updatePromotional'])->name('term.promotional.update');
    Route::post('term/deleteterm', [SchooltermController::class, 'deleteterm'])->name('term.deleteterm');
    Route::post('term/updateterm', [SchooltermController::class, 'updateterm'])->name('term.updateterm');

    // ===================================================================
    // SCHOOL ARM / CLASS / CLUB / SPORT / HOUSE
    // ===================================================================
    Route::prefix('schoolarm')->group(function () {
        Route::get('/data', [SchoolArmController::class, 'data'])->name('schoolarm.data');
        Route::get('/stats', [SchoolArmController::class, 'stats'])->name('schoolarm.stats');
        Route::post('/bulk-destroy', [SchoolArmController::class, 'deleteMultiple'])->name('schoolarm.bulk-destroy');
        Route::post('/update-arm', [SchoolArmController::class, 'updatearm'])->name('schoolarm.updatearm');
        Route::post('/delete-arm', [SchoolArmController::class, 'deletearm'])->name('schoolarm.deletearm');
    });
    Route::resource('schoolarm', SchoolArmController::class);

    Route::prefix('schoolclass')->group(function () {
        Route::get('/data', [SchoolClassController::class, 'data'])->name('schoolclass.data');
        Route::get('/stats', [SchoolClassController::class, 'stats'])->name('schoolclass.stats');
        Route::post('/bulk-destroy', [SchoolClassController::class, 'deleteMultiple'])->name('schoolclass.bulk-destroy');
    });
    Route::resource('schoolclass', SchoolClassController::class);

    Route::prefix('club')->group(function () {
        Route::get('/data', [ClubController::class, 'data'])->name('club.data');
        Route::get('/stats', [ClubController::class, 'stats'])->name('club.stats');
        Route::post('/bulk-destroy', [ClubController::class, 'deleteMultiple'])->name('club.bulk-destroy');
        Route::post('/update-club', [ClubController::class, 'updateclub'])->name('club.updateclub');
        Route::post('/delete-club', [ClubController::class, 'deleteclub'])->name('club.deleteclub');
    });
    Route::resource('club', ClubController::class);

    Route::prefix('sport')->group(function () {
        Route::get('/data', [SportController::class, 'data'])->name('sport.data');
        Route::get('/stats', [SportController::class, 'stats'])->name('sport.stats');
        Route::post('/bulk-destroy', [SportController::class, 'deleteMultiple'])->name('sport.bulk-destroy');
        Route::post('/update-sport', [SportController::class, 'updatesport'])->name('sport.updatesport');
        Route::post('/delete-sport', [SportController::class, 'deletesport'])->name('sport.deletesport');
    });
    Route::resource('sport', SportController::class);

    Route::prefix('schoolhouse')->group(function () {
        Route::get('/data', [SchoolHouseController::class, 'data'])->name('schoolhouse.data');
        Route::get('/stats', [SchoolHouseController::class, 'stats'])->name('schoolhouse.stats');
        Route::post('/bulk-destroy', [SchoolHouseController::class, 'deleteMultiple'])->name('schoolhouse.bulk-destroy');
        Route::post('/update-house', [SchoolHouseController::class, 'updatehouse'])->name('schoolhouse.updatehouse');
        Route::post('/delete-house', [SchoolHouseController::class, 'deletehouse'])->name('schoolhouse.deletehouse');
    });
    Route::resource('schoolhouse', SchoolHouseController::class);

    // ===================================================================
    // STUDENT MANAGEMENT
    // ===================================================================
    Route::prefix('student')->group(function () {
        Route::get('bulkupload', [StudentController::class, 'bulkupload'])->name('student.bulkupload');
        Route::post('bulkuploadsave', [StudentController::class, 'bulkuploadsave'])->name('student.bulkuploadsave');
        Route::get('batchindex', [StudentController::class, 'batchindex'])->name('studentbatchindex');
        Route::delete('deletestudentbatch', [StudentController::class, 'deletestudentbatch'])->name('student.deletestudentbatch');
        Route::get('batch/generate-template', [StudentController::class, 'generateBatchTemplate'])->name('student.batch.generateTemplate');
        Route::post('batch/generate-template-bulk', [StudentController::class, 'generateBatchTemplateBulk'])->name('student.batch.generateTemplateBulk');
        Route::get('batch/import-progress', [StudentController::class, 'getBatchImportProgress'])->name('student.batch.importProgress');
        Route::get('batch/{id}/errors', [StudentController::class, 'getBatchImportErrors'])->name('student.batch.errors');
        Route::post('batch/bulk-delete', [StudentController::class, 'deleteStudentBatchMultiple'])->name('student.batch.bulkDelete');
    });

    // Class & term operations
    Route::get('student/class-operations', [StudentClassOperationsController::class, 'index'])->name('student.class-operations');

    Route::get('students-in-term', [StudentClassOperationsController::class, 'getStudentsInTerm'])->name('students.in-term');
    Route::post('students/remove-from-term', [StudentClassOperationsController::class, 'removeFromTerm'])->name('students.remove-from-term');
    Route::post('students/bulk-remove-from-term', [StudentClassOperationsController::class, 'bulkRemoveFromTerm'])->name('students.bulk-remove-from-term');
    Route::get('students/by-class-session', [StudentClassOperationsController::class, 'getStudentsByClassAndSession'])->name('students.by-class-session');
    Route::post('students/bulk-update-status', [StudentClassOperationsController::class, 'bulkUpdateStatus'])->name('students.bulk-update-status');
    Route::post('student/{studentId}/update-current-term', [StudentClassOperationsController::class, 'updateCurrentTerm'])->name('student.update-current-term');
    Route::post('students/bulk-update-current-term', [StudentClassOperationsController::class, 'bulkUpdateCurrentTerm'])->name('students.bulk-update-current-term');

    // Optimized listing + AJAX
    Route::get('students/optimized', [StudentController::class, 'getStudentsOptimized'])->name('students.optimized');
    Route::get('students/data', [StudentController::class, 'data'])->name('students.data');
    Route::get('students/last-admission-number', [StudentController::class, 'getLastAdmissionNumber'])->name('students.last-admission-number');
    Route::get('students/report', [StudentController::class, 'generateReport'])->name('students.report');
    Route::get('students/report-progress', [StudentController::class, 'getReportProgress'])->name('students.report-progress');

    // Single-student read helpers
    Route::get('student/{id}/current-term', [StudentController::class, 'getCurrentTerm'])->name('student.current-term');
    Route::get('student/{id}/active-term', [StudentController::class, 'getActiveTerm'])->name('student.active-term');
    Route::get('student/{id}/current-info', [StudentController::class, 'getCurrentInfo'])->name('student.current-info');
    Route::get('student/{id}/registered-terms', [StudentController::class, 'getAllRegisteredTerms'])->name('student.registered-terms');
    Route::get('student/{id}/all-terms', [StudentController::class, 'getAllRegisteredTerms'])->name('student.all-terms');

    // Individual student operations
    Route::prefix('student')->name('student.')->group(function () {
        Route::delete('{id}/destroy', [StudentController::class, 'destroy'])->name('destroy');
        Route::get('studentid/{studentid}', [StudentController::class, 'deletestudent'])->name('deletestudent');
        Route::get('overview/{id}', [StudentController::class, 'overview'])->name('overview');
        Route::get('settings/{id}', [StudentController::class, 'setting'])->name('settings');
        Route::put('updateclass', [StudentController::class, 'updateClass'])->name('updateclass');
        Route::post('generate-student-pdf', [StudentController::class, 'generateStudentPdf'])->name('pdf');
    });

    // System utility
    Route::get('/system/active-term-session', function () {
        $activeTerm    = \App\Models\Schoolterm::where('status', true)->first();
        $activeSession = \App\Models\Schoolsession::where('status', 'Current')->first();

        return response()->json([
            'success' => true,
            'term'    => $activeTerm    ? ['id' => $activeTerm->id,       'term'    => $activeTerm->term,       'status' => $activeTerm->status]    : null,
            'session' => $activeSession ? ['id' => $activeSession->id,    'session' => $activeSession->session, 'status' => $activeSession->status] : null,
        ]);
    })->name('system.active-term-session');

    // Resource route (last, so fixed-segment paths above aren't swallowed)
    Route::resource('student', StudentController::class)
        ->except(['destroy'])
        ->whereNumber('student');

    // ===================================================================
    // REPORTS (student results)
    // ===================================================================
    Route::get('/reports/progress', [StudentResultsController::class, 'getReportProgress'])->name('reports.progress');
    Route::post('/reports/generate', [StudentResultsController::class, 'generateReport'])->name('reports.generate');

    // ===================================================================
    // CLASS OPERATIONS & CATEGORIES
    // ===================================================================
    Route::resource('classoperation', ClassOperationController::class);

    Route::prefix('classcategories')->group(function () {
        Route::get('/data', [ClasscategoryController::class, 'data'])->name('classcategories.data');
        Route::get('/stats', [ClasscategoryController::class, 'stats'])->name('classcategories.stats');
        Route::post('/bulk-destroy', [ClasscategoryController::class, 'deleteMultiple'])->name('classcategories.bulk-destroy');
        Route::post('/update-category', [ClasscategoryController::class, 'updateclasscategory'])->name('classcategories.updateclasscategory');
    });
    Route::resource('classcategories', ClasscategoryController::class);

    // ===================================================================
    // PARENTS
    // ===================================================================
    Route::get('/parents', [ParentController::class, 'index'])->name('parent.index');
    Route::get('/parents/optimized', [ParentController::class, 'getParentsOptimized'])->name('parent.optimized');
    Route::get('/parents/students-without-parent', [ParentController::class, 'getStudentsWithoutParent'])->name('parent.students.without');
    Route::post('/parents', [ParentController::class, 'store'])->name('parent.store');
    Route::get('/parents/{id}', [ParentController::class, 'show'])->name('parent.show');
    Route::get('/parents/{id}/edit', [ParentController::class, 'edit'])->name('parent.edit');
    Route::patch('/parents/{id}', [ParentController::class, 'update'])->name('parent.update');
    Route::delete('/parents/{id}', [ParentController::class, 'destroy'])->name('parent.destroy');
    Route::post('/parents/destroy-multiple', [ParentController::class, 'destroyMultiple'])->name('parent.destroy.multiple');

    // ===================================================================
    // MY CLASS / MY SUBJECT / RESULT ROOM
    // ===================================================================
    Route::resource('studentImageUpload', StudentImageUploadController::class);
    Route::resource('myclass', MyClassController::class);
    // Read-only for teachers -- assignments are managed on the Subject Teacher / Subject Class screens.
    Route::resource('mysubject', MySubjectController::class)->only(['index']);

    Route::get('/myresultroom', [MyresultroomController::class, 'index'])->name('myresultroom.index');
    Route::post('/myresultroom', [MyresultroomController::class, 'index']);
    Route::post('/myresultroom/store', [MyresultroomController::class, 'store']);
    Route::delete('/subjects/registered-classes', [MyresultroomController::class, 'delete']);

    Route::resource('studentresults', StudentResultsController::class);

    // ===================================================================
    // SCORESHEET
    // ===================================================================
    Route::get('scoresheet/download-marks-sheet', [MyScoreSheetController::class, 'downloadMarksSheet'])->name('scoresheet.download-marks-sheet');
    Route::post('subjectscoresheet/update-arm-positions-all', [MyScoreSheetController::class, 'updateAllArmPositions'])->name('update.arm.positions.all');
    Route::get('/subjectscoresheet/import-progress', [MyScoreSheetController::class, 'importProgress'])->name('subjectscoresheet.import_progress');
    Route::post('/subjectscoresheet/clear-progress', [MyScoreSheetController::class, 'clearImportProgress'])->name('subjectscoresheet.clear_progress');

    Route::get('subjectscoresheet/{schoolclassid}/{subjectclassid}/{staffid}/{termid}/{sessionid}', [MyScoreSheetController::class, 'subjectscoresheet'])->name('subjectscoresheet');
    Route::get('subjectscoresheet/edit/{id}', [MyScoreSheetController::class, 'edit'])->name('subjectscoresheet.edit');
    Route::put('subjectscoresheet/update/{id}', [MyScoreSheetController::class, 'update'])->name('subjectscoresheet.update');
    Route::delete('subjectscoresheet/delete/{id}', [MyScoreSheetController::class, 'destroy'])->name('subjectscoresheet.destroy');
    Route::get('subjectscoresheet/export', [MyScoreSheetController::class, 'export'])->name('subjectscoresheet.export');
    Route::post('subjectscoresheet/import', [MyScoreSheetController::class, 'import'])->name('subjectscoresheet.import');
    Route::get('subjectscoresheet/results', [MyScoreSheetController::class, 'results'])->name('subjectscoresheet.results');
    Route::post('subjectscoresheet/grade-preview', [MyScoreSheetController::class, 'calculateGradePreview'])->name('subjectscoresheet.grade-preview');
    Route::post('subjectscoresheet/bulk-update', [MyScoreSheetController::class, 'bulkUpdateScores'])->name('subjectscoresheet.bulk-update');
    Route::post('subjectscoresheet/single-update', [MyScoreSheetController::class, 'singleUpdateScore'])->name('subjectscoresheet.single-update');
    Route::get('scoresheet/download-scores-pdf', [MyScoreSheetController::class, 'downloadScoresPdf'])->name('scoresheet.download-scores-pdf');
    Route::post('subjectscoresheet/grade-for-score', [MyScoreSheetController::class, 'calculateGradeForScore'])->name('subjectscoresheet.grade-for-score');

    Route::post('/studentreports/column-options', [ViewStudentReportController::class, 'getColumnOptions'])->name('studentreports.column-options');
    Route::get('/studentreport/drawer-data/{studentId}/{schoolclassId}/{sessionId}/{termId}', [ViewStudentReportController::class, 'drawerData'])->name('studentreport.drawer-data');

    // Mock scoresheet
    Route::get('subjectscoresheet-mock', [MyScoreSheetController::class, 'mockIndex'])->name('subjectscoresheet-mock.index');
    Route::get('subjectscoresheet-mock/export', [MyScoreSheetController::class, 'mockExport'])->name('subjectscoresheet-mock.export');
    Route::get('subjectscoresheet-mock/results', [MyScoreSheetController::class, 'mockResults'])->name('subjectscoresheet-mock.results');
    Route::get('subjectscoresheet-mock/download-marksheet', [MyScoreSheetController::class, 'mockDownloadMarkSheet'])->name('subjectscoresheet-mock.download-marksheet');
    Route::get('subjectscoresheet-mock/{schoolclassid}/{subjectclassid}/{staffid}/{termid}/{sessionid}', [MyScoreSheetController::class, 'mockSubjectscoresheet'])->name('subjectscoresheet-mock.show');
    Route::post('subjectscoresheet-mock/import', [MyScoreSheetController::class, 'mockImport'])->name('subjectscoresheet-mock.import');
    Route::get('subjectscoresheet-mock/{id}/edit', [MyScoreSheetController::class, 'mockEdit'])->name('subjectscoresheet-mock.edit');
    Route::put('subjectscoresheet-mock/{id}', [MyScoreSheetController::class, 'mockUpdate'])->name('subjectscoresheet-mock.update');
    Route::post('scoresheet-mock/destroy', [MyScoreSheetController::class, 'mockDestroy'])->name('scoresheet-mock.destroy');
    Route::post('scoresheet-mock/bulk-update', [MyScoreSheetController::class, 'mockBulkUpdateScores'])->name('scoresheet-mock.bulk-update');
    Route::post('subjectscoresheet-mock/calculate-grade', [MyScoreSheetController::class, 'calculateGradeForScore'])->name('subjectscoresheet-mock.calculate-grade');
    Route::post('scoresheet-mock/single-update', [MyScoreSheetController::class, 'mockSingleUpdateScore'])->name('scoresheet-mock.single-update');

    Route::get('/subassessment/scoresheet/{schoolclassid}/{subjectclassid}/{staffid}/{termid}/{sessionid}/{subassessmentid}', [MyScoreSheetController::class, 'subassessmentScoresheet'])->name('subassessment.scoresheet');
    Route::get('/assessment/scoresheet/{schoolclassid}/{subjectclassid}/{staffid}/{termid}/{sessionid}/{assessmentid}', [MyScoreSheetController::class, 'assessmentScoresheet'])->name('assessment.scoresheet');

    // Student assessments
    Route::get('/studentassessments', [StudentAssessmentController::class, 'index'])->name('assessments');
    Route::get('/studentassessments/print', [StudentAssessmentController::class, 'printResult'])->name('assessments.print');
    Route::get('assessments/print-mock', [StudentAssessmentController::class, 'printMockResult'])->name('assessments.print.mock');

    // Student payments (student-facing)
    Route::get('/my-payments', [StudentPaymentController::class, 'index'])->name('student.payments');
    Route::get('/my-payments/receipt', [StudentPaymentController::class, 'printReceipt'])->name('student.payments.receipt');
    Route::get('/my-payments/pay', [OnlineFeeController::class, 'myFees'])->name('student.fees.pay');

    // School notices to parents/staff (SMS, WhatsApp, email)
    Route::prefix('notices')->name('notices.')->group(function () {
        Route::get('/', [SchoolNoticeController::class, 'index'])->name('index');
        Route::get('/create', [SchoolNoticeController::class, 'create'])->name('create');
        Route::post('/', [SchoolNoticeController::class, 'store'])->name('store');
        Route::post('/preview', [SchoolNoticeController::class, 'preview'])->name('preview');
        Route::post('/test', [SchoolNoticeController::class, 'test'])->name('test');
        Route::get('/students', [SchoolNoticeController::class, 'searchStudents'])->name('students');
        Route::get('/settings', [MessagingSettingsController::class, 'index'])->name('settings');
        Route::put('/settings/{channel}', [MessagingSettingsController::class, 'update'])->whereIn('channel', ['sms', 'whatsapp', 'email'])->name('settings.update');
        Route::put('/settings/receipts', [MessagingSettingsController::class, 'receipts'])->name('settings.receipts');
        Route::post('/settings/{channel}/test', [MessagingSettingsController::class, 'test'])->whereIn('channel', ['sms', 'whatsapp', 'email'])->name('settings.test');
        Route::get('/automations', [AutoMessageController::class, 'index'])->name('automations');
        Route::put('/automations/{type}', [AutoMessageController::class, 'update'])->whereIn('type', ['absence', 'fees', 'birthday'])->name('automations.update');
        Route::post('/automations/{type}/preview', [AutoMessageController::class, 'preview'])->whereIn('type', ['absence', 'fees', 'birthday'])->name('automations.preview');
        Route::match(['post', 'put'], '/automations/{type}/run', [AutoMessageController::class, 'run'])->whereIn('type', ['absence', 'fees', 'birthday'])->name('automations.run');
        Route::get('/{notice}', [SchoolNoticeController::class, 'show'])->whereNumber('notice')->name('show');
        Route::get('/{notice}/edit', [SchoolNoticeController::class, 'edit'])->whereNumber('notice')->name('edit');
        Route::put('/{notice}', [SchoolNoticeController::class, 'update'])->whereNumber('notice')->name('update');
        Route::delete('/{notice}', [SchoolNoticeController::class, 'destroy'])->whereNumber('notice')->name('destroy');
        Route::post('/{notice}/cancel', [SchoolNoticeController::class, 'cancel'])->whereNumber('notice')->name('cancel');
        Route::post('/{notice}/resend', [SchoolNoticeController::class, 'resendFailed'])->whereNumber('notice')->name('resend');
        Route::post('/{notice}/duplicate', [SchoolNoticeController::class, 'duplicate'])->whereNumber('notice')->name('duplicate');
    });

    // In-portal notifications (bell)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');

    // Parent contact clean-up (+ CSV import / export)
    Route::prefix('parent-contacts')->name('parent-contacts.')->group(function () {
        Route::get('/', [ParentContactController::class, 'index'])->name('index');
        Route::get('/export', [ParentContactController::class, 'export'])->name('export');
        Route::post('/import', [ParentContactController::class, 'import'])->name('import');
        Route::put('/{student}', [ParentContactController::class, 'update'])->whereNumber('student')->name('update');
    });

    // Send report cards to parents (email / WhatsApp / SMS link)
    Route::prefix('result-sends')->name('result-sends.')->group(function () {
        Route::get('/', [ResultSendController::class, 'index'])->name('index');
        Route::get('/create', [ResultSendController::class, 'create'])->name('create');
        Route::post('/candidates', [ResultSendController::class, 'candidates'])->name('candidates');
        Route::post('/', [ResultSendController::class, 'store'])->name('store');
        Route::get('/{send}', [ResultSendController::class, 'show'])->whereNumber('send')->name('show');
        Route::post('/{send}/resend', [ResultSendController::class, 'resend'])->whereNumber('send')->name('resend');
        Route::post('/{send}/cancel', [ResultSendController::class, 'cancel'])->whereNumber('send')->name('cancel');
        Route::get('/item/{item}/pdf', [ResultSendController::class, 'pdf'])->whereNumber('item')->name('pdf');
    });

    // Online school-fee payments (Paystack)
    Route::prefix('online-fees')->name('online-fees.')->group(function () {
        Route::get('/', [OnlineFeeController::class, 'index'])->name('index');
        Route::get('/students', [OnlineFeeController::class, 'searchStudents'])->name('students');
        Route::get('/pay/{student}', [OnlineFeeController::class, 'payFor'])->whereNumber('student')->name('pay-for');
        Route::post('/checkout', [OnlineFeeController::class, 'initialize'])->name('checkout');
        Route::get('/callback', [OnlineFeeController::class, 'callback'])->name('callback');
        Route::get('/transaction/{reference}', [OnlineFeeController::class, 'show'])->name('show');
        Route::get('/transaction/{reference}/status', [OnlineFeeController::class, 'status'])->name('status');
        Route::post('/transaction/{reference}/verify', [OnlineFeeController::class, 'verify'])->name('verify');
    });

    // Result access control (who can see results while owing fees)
    Route::get('/result-access', [ResultAccessController::class, 'index'])->name('result-access.index');
    Route::post('/result-access/settings', [ResultAccessController::class, 'saveSettings'])->name('result-access.settings');
    Route::get('/result-access/students', [ResultAccessController::class, 'students'])->name('result-access.students');
    Route::post('/result-access/grant', [ResultAccessController::class, 'grant'])->name('result-access.grant');
    Route::post('/result-access/revoke', [ResultAccessController::class, 'revoke'])->name('result-access.revoke');
    Route::post('/result-access/manual', [ResultAccessController::class, 'toggleManual'])->name('result-access.manual');
    Route::get('/result-access/history', [ResultAccessController::class, 'history'])->name('result-access.history');

    // ===================================================================
    // SCHOOL INFORMATION
    // ===================================================================
    Route::prefix('school-info')->name('admin.school-info.')->group(function () {
        Route::get('/', [SchoolInformationController::class, 'index'])->name('index');
        Route::post('/', [SchoolInformationController::class, 'store'])->name('store');
        Route::match(['PUT', 'PATCH', 'POST'], '/{id}', [SchoolInformationController::class, 'update'])->name('update');
        Route::delete('/{id}', [SchoolInformationController::class, 'destroy'])->name('destroy');
        Route::get('/{id}', [SchoolInformationController::class, 'show'])->name('show');
        Route::get('/{id}/edit-json', [SchoolInformationController::class, 'editJson'])->name('edit-json');
        Route::post('/bulk-delete', [SchoolInformationController::class, 'bulkDestroy'])->name('bulk-destroy');
    });
    Route::resource('school-information', SchoolInformationController::class);

    // ===================================================================
    // SCHOOL BILLS
    // ===================================================================
    Route::prefix('schoolbill')->name('schoolbill.')->group(function () {
        Route::get('/', [SchoolBillController::class, 'index'])->name('index');
        Route::post('/store', [SchoolBillController::class, 'store'])->name('store');
        Route::post('/bulk-destroy', [SchoolBillController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::get('/{id}/edit-json', [SchoolBillController::class, 'edit'])->name('edit-json');
        Route::get('/{id}', [SchoolBillController::class, 'show'])->name('show');
        Route::put('/{id}', [SchoolBillController::class, 'update'])->name('update');
        Route::delete('/{id}', [SchoolBillController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('schoolbilltermsession')->name('schoolbilltermsession.')->group(function () {
        Route::get('/', [SchoolBillTermSessionController::class, 'index'])->name('index');
        Route::get('/data', [SchoolBillTermSessionController::class, 'data'])->name('data');
        Route::get('/stats', [SchoolBillTermSessionController::class, 'stats'])->name('stats');
        Route::post('/store', [SchoolBillTermSessionController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [SchoolBillTermSessionController::class, 'edit'])->name('edit');
        Route::put('/{id}', [SchoolBillTermSessionController::class, 'update'])->name('update');
        Route::delete('/{id}', [SchoolBillTermSessionController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/related', [SchoolBillTermSessionController::class, 'getRelated'])->name('related');
        Route::post('/bulk-destroy', [SchoolBillTermSessionController::class, 'bulkDestroy'])->name('bulk-destroy');
    });
    Route::resource('schoolbilltermsession', SchoolBillTermSessionController::class);
    Route::get('/schoolbilltermsessionid/{schoolbilltermsessionid}', [SchoolBillTermSessionController::class, 'deleteschoolbilltermsession'])->name('schoolbilltermsession.deleteschoolbilltermsession');
    Route::post('schoolbilltermsessionbid', [SchoolBillTermSessionController::class, 'updateschoolbilltermsession'])->name('schoolbilltermsession.updateschoolbilltermsession');

    // ===================================================================
    // SCHOLARSHIP / DISCOUNT / SIBLING / GATEWAYS
    // ===================================================================
    Route::prefix('admin/scholarship')->name('admin.scholarship.')->group(function () {
        Route::get('/assignments', [ScholarshipController::class, 'showAssignments'])->name('assignments');
        Route::get('/applications', [ScholarshipController::class, 'showApplications'])->name('applications');
        Route::get('/create', [ScholarshipController::class, 'create'])->name('create');
        Route::post('/store', [ScholarshipController::class, 'store'])->name('store');
        Route::post('/bulk-destroy', [ScholarshipController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/assign', [ScholarshipController::class, 'assignToStudent'])->name('assign');
        Route::delete('/assignment/{assignmentId}', [ScholarshipController::class, 'revokeAssignment'])->name('assignment.revoke');
        Route::post('/application/{applicationId}/approve', [ScholarshipController::class, 'approveApplication'])->name('application.approve');
        Route::post('/application/{applicationId}/reject', [ScholarshipController::class, 'rejectApplication'])->name('application.reject');
        Route::get('/eligible-students', [ScholarshipController::class, 'getEligibleStudents'])->name('eligible-students');
        Route::get('/', [ScholarshipController::class, 'index'])->name('index');
        Route::get('/{id}', [ScholarshipController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ScholarshipController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ScholarshipController::class, 'update'])->name('update');
        Route::delete('/{id}', [ScholarshipController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/approve', [ScholarshipController::class, 'approve'])->name('approve');
        Route::post('/{id}/revoke', [ScholarshipController::class, 'revoke'])->name('revoke');
    });

    Route::prefix('admin/discount')->name('admin.discount.')->group(function () {
        Route::get('/assignments', [DiscountController::class, 'showAssignments'])->name('assignments');
        Route::get('/create', [DiscountController::class, 'create'])->name('create');
        Route::post('/store', [DiscountController::class, 'store'])->name('store');
        Route::post('/bulk-destroy', [DiscountController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/assign', [DiscountController::class, 'assignToStudent'])->name('assign');
        Route::delete('/assignment/{assignmentId}', [DiscountController::class, 'removeAssignment'])->name('assignment.remove');
        Route::get('/eligible-students', [DiscountController::class, 'getEligibleStudents'])->name('eligible-students');
        Route::get('/', [DiscountController::class, 'index'])->name('index');
        Route::get('/{id}', [DiscountController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [DiscountController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DiscountController::class, 'update'])->name('update');
        Route::delete('/{id}', [DiscountController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/approve', [DiscountController::class, 'approve'])->name('approve');
    });

    Route::prefix('sibling')->name('sibling.')->group(function () {
        Route::get('/create', [SiblingGroupController::class, 'create'])->name('create');
        Route::post('/store', [SiblingGroupController::class, 'store'])->name('store');
        Route::get('/search-students', [SiblingGroupController::class, 'searchStudents'])->name('search-students');
        Route::post('/apply-discount', [SiblingGroupController::class, 'applyDiscount'])->name('apply-discount');
        Route::get('/student/{studentId}', [SiblingGroupController::class, 'getStudentSiblings'])->name('student-siblings');
        Route::get('/', [SiblingGroupController::class, 'index'])->name('index');
        Route::get('/{id}', [SiblingGroupController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [SiblingGroupController::class, 'edit'])->name('edit');
        Route::put('/{id}', [SiblingGroupController::class, 'update'])->name('update');
        Route::delete('/{id}', [SiblingGroupController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin/payment-gateways')->name('admin.payment-gateways.')->group(function () {
        Route::get('/', [PaymentGatewayController::class, 'index'])->name('index');
        Route::post('/{gateway}/toggle', [PaymentGatewayController::class, 'toggleGateway'])->name('toggle');
        Route::put('/{gateway}', [PaymentGatewayController::class, 'updateConfig'])->name('update');
        Route::post('/test/{gateway}', [PaymentGatewayController::class, 'testGateway'])->name('test');
    });

    // ===================================================================
    // PAYMENTS
    // ===================================================================
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/', [SchoolPaymentController::class, 'index'])->name('index');
        Route::get('/data', [SchoolPaymentController::class, 'data'])->name('data');
        Route::get('/stats', [SchoolPaymentController::class, 'stats'])->name('stats');
        Route::get('/term-session/{id}', [SchoolPaymentController::class, 'termSession'])->name('termsession');
        Route::get('/details/{studentId}/{classId}/{termId}/{sessionId}', [SchoolPaymentController::class, 'showPaymentDetails'])->name('details');
        Route::get('/get-payment-details', [SchoolPaymentController::class, 'getPaymentDetailsAjax'])->name('getPaymentDetailsAjax');
        Route::post('/store', [SchoolPaymentController::class, 'store'])->name('store');
        Route::post('/bulk-store', [SchoolPaymentController::class, 'bulkStore'])->name('bulk-store');
        Route::post('/delete/{recordId}', [SchoolPaymentController::class, 'deletestudentpayment'])->name('delete');
        Route::get('/invoice/{studentId}/{schoolclassid}/{termid}/{sessionid}', [SchoolPaymentController::class, 'invoice'])->name('invoice');
        Route::post('/invoice/confirm/{studentId}/{schoolclassid}/{termid}/{sessionid}', [SchoolPaymentController::class, 'confirmInvoice'])->name('confirmInvoice');
        Route::get('/statement/{studentId}/{schoolclassid}/{termid}/{sessionid}', [SchoolPaymentController::class, 'statement'])->name('statement');
        Route::get('/termsessionpayments', [SchoolPaymentController::class, 'termsessionpayments'])->name('termsessionpayments');
    });

    Route::prefix('schoolpayment')->name('schoolpayment.')->group(function () {
        Route::get('/', [SchoolPaymentController::class, 'index'])->name('index');
        Route::get('/data', [SchoolPaymentController::class, 'data'])->name('data');
        Route::get('/stats', [SchoolPaymentController::class, 'stats'])->name('stats');
        Route::get('/term-session/{id}', [SchoolPaymentController::class, 'termSession'])->name('termsession');
        Route::get('/termsessionpayments', [SchoolPaymentController::class, 'termsessionpayments'])->name('termsessionpayments');
        Route::get('/get-payment-details', [SchoolPaymentController::class, 'getPaymentDetailsAjax'])->name('getPaymentDetailsAjax');
        Route::post('/store', [SchoolPaymentController::class, 'store'])->name('store');
        Route::get('/arrears/{studentId}', [SchoolPaymentController::class, 'arrearsDetails'])->name('schoolpayment.arrears');
        Route::post('/bulk-store', [SchoolPaymentController::class, 'bulkStore'])->name('bulk-store');
        Route::post('/delete/{recordId}', [SchoolPaymentController::class, 'deletestudentpayment'])->name('deletestudentpayment');
        Route::get('/invoice/{studentId}/{schoolclassid}/{termid}/{sessionid}', [SchoolPaymentController::class, 'invoice'])->name('invoice');
        Route::post('/invoice/confirm/{studentId}/{schoolclassid}/{termid}/{sessionid}', [SchoolPaymentController::class, 'confirmInvoice'])->name('confirmInvoice');
        Route::get('/statement/{studentId}/{schoolclassid}/{termid}/{sessionid}', [SchoolPaymentController::class, 'statement'])->name('statement');
    });

    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/', [EnhancedSchoolPaymentController::class, 'index'])->name('index');
        Route::get('/student/{studentId}/class/{classId}/term/{termId}/session/{sessionId}', [EnhancedSchoolPaymentController::class, 'showPaymentDetails'])->name('details');
        Route::get('/flexible/{studentId}/{classId}/{termId}/{sessionId}', [EnhancedSchoolPaymentController::class, 'showFlexiblePayment'])->name('flexible');
        Route::post('/offline/process', [EnhancedSchoolPaymentController::class, 'processOfflinePayment'])->name('offline.process');
        Route::post('/invoice/generate/{paymentId}', [EnhancedSchoolPaymentController::class, 'generateInvoice'])->name('invoice.generate');
        Route::get('/invoice/{studentId}/{classId}/{termId}/{sessionId}', [EnhancedSchoolPaymentController::class, 'showInvoice'])->name('invoice');
        Route::get('/invoice/download/{studentId}/{classId}/{termId}/{sessionId}', [EnhancedSchoolPaymentController::class, 'showInvoice'])->name('invoice.download');
        Route::get('/receipt/{batchId}', [EnhancedSchoolPaymentController::class, 'showReceipt'])->name('receipt');
        Route::post('/reverse/{batchId}', [EnhancedSchoolPaymentController::class, 'reversePayment'])->name('reverse');
        Route::get('/status/{studentId}/{classId}/{termId}/{sessionId}', [EnhancedSchoolPaymentController::class, 'getPaymentStatus'])->name('status');
        Route::get('/savings/{studentId}', [EnhancedSchoolPaymentController::class, 'getSavingsSummary'])->name('savings');
        Route::get('/history', [EnhancedSchoolPaymentController::class, 'getPaymentHistory'])->name('history');
        Route::get('/details/ajax', [EnhancedSchoolPaymentController::class, 'getPaymentStatusAjax'])->name('details.ajax');
    });

    // The old online-payment screens (OnlinePaymentController) depended on a model
    // that no longer exists. Only the index is kept, pointing at the new page.
    Route::prefix('payment/online')->name('payment.online.')->group(function () {
        Route::get('/', [OnlineFeeController::class, 'index'])->name('index');
    });

    Route::prefix('bulk-payment')->name('bulk-payment.')->group(function () {
        Route::get('/', [EnhancedSchoolPaymentController::class, 'bulkPaymentForm'])->name('form');
        Route::post('/upload', [EnhancedSchoolPaymentController::class, 'uploadBulkPayment'])->name('upload');
        Route::post('/process', [EnhancedSchoolPaymentController::class, 'processBulkPayment'])->name('process');
        Route::get('/template', [EnhancedSchoolPaymentController::class, 'downloadTemplate'])->name('template');
        Route::get('/status/{batchId}', [EnhancedSchoolPaymentController::class, 'getBulkStatus'])->name('status');
    });

    // ===================================================================
    // FINANCIAL REPORTS
    // ===================================================================
    Route::prefix('reports/financial')->name('reports.financial.')->group(function () {
        Route::get('/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/balance-sheet/export', [FinancialReportController::class, 'exportBalanceSheet'])->name('balance-sheet.export');
        Route::get('/income-statement', [FinancialReportController::class, 'incomeStatement'])->name('income-statement');
        Route::get('/income-statement/export', [FinancialReportController::class, 'exportIncomeStatement'])->name('income-statement.export');
        Route::get('/trial-balance', [FinancialReportController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/cash-flow', [FinancialReportController::class, 'cashFlow'])->name('cash-flow');
        Route::get('/cash-flow/export', [FinancialReportController::class, 'exportCashFlow'])->name('cash-flow.export');
        Route::get('/debtors', [FinancialReportController::class, 'debtorsList'])->name('debtors');
        Route::get('/debtors/export/{format}', [FinancialReportController::class, 'exportDebtors'])->name('export');
        Route::get('/collection-summary', [FinancialReportController::class, 'collectionSummary'])->name('collection-summary');
        Route::get('/scholarship-impact', [FinancialReportController::class, 'scholarshipImpact'])->name('scholarship-impact');
    });

    // ===================================================================
    // ANALYSIS REPORTS
    // ===================================================================
    Route::prefix('reports/analysis')->name('reports.analysis.')->group(function () {
        Route::get('/', [AnalysisReportController::class, 'index'])->name('index');
        Route::get('/class', [AnalysisReportController::class, 'getClassAnalysisData'])->name('class');
        Route::get('/class-data', [AnalysisReportController::class, 'getClassAnalysisData'])->name('class-data');
        Route::get('/class-details', [AnalysisReportController::class, 'analysisClassTermSession'])->name('class-details');
        Route::get('/export', [AnalysisReportController::class, 'exportClassAnalysis'])->name('export');
        Route::get('/export-pdf/{class_id}/{termid_id}/{session_id}/{action?}', [AnalysisReportController::class, 'exportPDF'])->name('export-pdf');
        Route::get('/school-wide', [AnalysisReportController::class, 'schoolWideAnalysis'])->name('school-wide');
        Route::get('/school-wide/export', [AnalysisReportController::class, 'exportSchoolWideAnalysis'])->name('school-wide.export');
        Route::get('/scholarship-impact', [AnalysisReportController::class, 'scholarshipImpactAnalysis'])->name('scholarship-impact');
        Route::get('/student/{studentId}/{classId}/{termId}/{sessionId}', [AnalysisReportController::class, 'studentPaymentDetails'])->name('student-details');
        Route::get('/high-outstanding', [AnalysisReportController::class, 'getHighOutstandingAlerts'])->name('high-outstanding');
        Route::post('/send-reminders', [AnalysisReportController::class, 'sendPaymentReminders'])->name('send-reminders');
        Route::post('/clear-cache', [AnalysisReportController::class, 'clearReportCache'])->name('clear-cache');
        Route::post('/send-reminders', [ReminderController::class, 'sendReminders'])->name('send-reminders');
    });

    // ===================================================================
    // STAFF PAYMENTS
    // ===================================================================
    Route::prefix('staff/payments')->name('staff.payments.')->group(function () {
        Route::get('/', [StaffPaymentController::class, 'index'])->name('index');
        Route::get('/create', [StaffPaymentController::class, 'create'])->name('create');
        Route::get('/dashboard', [StaffPaymentController::class, 'staffDashboard'])->name('dashboard');
        Route::get('/history', [StaffPaymentController::class, 'getPaymentHistory'])->name('history');
        Route::post('/store', [StaffPaymentController::class, 'store'])->name('store');
        Route::get('/{id}', [StaffPaymentController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{id}/edit', [StaffPaymentController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [StaffPaymentController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [StaffPaymentController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::post('/reverse/{paymentId}', [StaffPaymentController::class, 'reversePayment'])->name('reverse');
        Route::post('/mark-paid/{paymentId}', [StaffPaymentController::class, 'markAsPaid'])->name('mark-paid');
        Route::get('/payslip/{payrollRunId}', [StaffPaymentController::class, 'viewPayslip'])->name('payslip');
        Route::get('/payslip/download/{payrollRunId}', [StaffPaymentController::class, 'downloadPayslip'])->name('payslip.download');
    });

    // ===================================================================
    // PAYROLL
    // ===================================================================
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/periods', [\App\Http\Controllers\Finance\PayrollReportsController::class, 'periods'])->name('periods');
        Route::post('/periods/create', [\App\Http\Controllers\Finance\PayrollReportsController::class, 'store'])->name('periods.create');
        Route::post('/periods', [PayrollController::class, 'createPeriod'])->name('periods.store');
        Route::post('/periods/{periodId}/process', [PayrollController::class, 'processPayroll'])->name('process');
        Route::post('/periods/{periodId}/approve', [PayrollController::class, 'approvePayroll'])->name('approve');
        Route::post('/periods/{periodId}/lock', [PayrollController::class, 'lockPeriod'])->name('lock');
        Route::get('/runs/{periodId}', [PayrollController::class, 'getPayrollRuns'])->name('runs');
        Route::get('/run/{payrollRunId}', [PayrollController::class, 'showPayrollRun'])->name('run.show');
        Route::post('/run/{payrollRunId}/pay', [PayrollController::class, 'processStaffPayment'])->name('run.pay');
        Route::get('/summary', [\App\Http\Controllers\Finance\PayrollReportsController::class, 'summary'])->name('summary');
        Route::get('/statutory', [\App\Http\Controllers\Finance\PayrollReportsController::class, 'statutory'])->name('statutory');
        Route::get('/salary-structures', [PayrollController::class, 'salaryStructures'])->name('salary-structures');
        Route::post('/salary-structures', [PayrollController::class, 'storeSalaryStructure'])->name('salary-structures.store');
        Route::get('/payroll/salary-structures/{id}', [PayrollController::class, 'showSalaryStructure'])->name('payroll.salary-structures.show');
        Route::get('/salary-structures/{id}/edit', [PayrollController::class, 'editSalaryStructure'])->name('salary-structures.edit');
        Route::put('/salary-structures/{id}', [PayrollController::class, 'updateSalaryStructure'])->name('salary-structures.update');
        Route::delete('/salary-structures/{id}', [PayrollController::class, 'destroySalaryStructure'])->name('salary-structures.destroy');
        Route::get('/structures', [PayrollController::class, 'salaryStructures'])->name('structures');

        // Setup: statutory rates & staff pay profiles
        Route::get('/rates', [\App\Http\Controllers\Finance\PayrollSetupController::class, 'rates'])->name('rates');
        Route::post('/rates', [\App\Http\Controllers\Finance\PayrollSetupController::class, 'storeRate'])->name('rates.store');
        Route::get('/profiles', [\App\Http\Controllers\Finance\PayrollSetupController::class, 'profiles'])->name('profiles');
        Route::post('/profiles/verify-account', [\App\Http\Controllers\Finance\PayrollSetupController::class, 'verifyAccount'])->middleware('throttle:20,1')->name('profiles.verify');
        Route::get('/profiles/{staff}', [\App\Http\Controllers\Finance\PayrollSetupController::class, 'editProfile'])->whereNumber('staff')->name('profiles.edit');
        Route::put('/profiles/{staff}', [\App\Http\Controllers\Finance\PayrollSetupController::class, 'saveProfile'])->whereNumber('staff')->name('profiles.save');
        // Government remittances
        Route::get('/remittances', [\App\Http\Controllers\Finance\RemittanceController::class, 'index'])->name('remittances');
        Route::post('/remittances/generate', [\App\Http\Controllers\Finance\RemittanceController::class, 'generate'])->name('remittances.generate');
        Route::get('/remittances/{remittance}', [\App\Http\Controllers\Finance\RemittanceController::class, 'show'])->whereNumber('remittance')->name('remittances.show');
        Route::get('/remittances/{remittance}/schedule', [\App\Http\Controllers\Finance\RemittanceController::class, 'schedule'])->whereNumber('remittance')->name('remittances.schedule');
        Route::post('/remittances/{remittance}/pay', [\App\Http\Controllers\Finance\RemittanceController::class, 'pay'])->whereNumber('remittance')->name('remittances.pay');
        Route::get('/remittances/{remittance}/receipt', [\App\Http\Controllers\Finance\RemittanceController::class, 'evidence'])->whereNumber('remittance')->name('remittances.evidence');

        // Payroll month screen
        Route::get('/month/{period}', [\App\Http\Controllers\Finance\PayrollMonthController::class, 'show'])->whereNumber('period')->name('month.show');
        Route::post('/month/{period}/calculate', [\App\Http\Controllers\Finance\PayrollMonthController::class, 'calculate'])->whereNumber('period')->name('month.calculate');
        Route::post('/month/{period}/approve', [\App\Http\Controllers\Finance\PayrollMonthController::class, 'approve'])->whereNumber('period')->name('month.approve');
        Route::post('/month/{period}/lock', [\App\Http\Controllers\Finance\PayrollMonthController::class, 'lock'])->whereNumber('period')->name('month.lock');
        Route::get('/month/{period}/register', [\App\Http\Controllers\Finance\PayrollMonthController::class, 'register'])->whereNumber('period')->name('month.register');
        Route::get('/month/{period}/bank-schedule', [\App\Http\Controllers\Finance\PayrollMonthController::class, 'bankSchedule'])->whereNumber('period')->name('month.bank');
        Route::post('/month/{period}/send', [\App\Http\Controllers\Finance\PayrollMonthController::class, 'send'])->whereNumber('period')->name('month.send');
        Route::get('/payslip-v2/{run}', [\App\Http\Controllers\Finance\PayrollMonthController::class, 'payslip'])->whereNumber('run')->name('month.payslip');
        Route::get('/payslip-v2/{run}/pdf', [\App\Http\Controllers\Finance\PayrollMonthController::class, 'payslipPdf'])->whereNumber('run')->name('month.payslip.pdf');
        Route::get('/employer', [\App\Http\Controllers\Finance\PayrollMonthController::class, 'employer'])->name('employer');
        Route::post('/employer', [\App\Http\Controllers\Finance\PayrollMonthController::class, 'saveEmployer'])->name('employer.save');
        Route::get('/profiles/{staff}/preview', [\App\Http\Controllers\Finance\PayrollSetupController::class, 'preview'])->whereNumber('staff')->name('profiles.preview');
        Route::post('/profiles/{staff}/placement', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'savePlacement'])->whereNumber('staff')->name('profiles.placement');

        // Salary scales, pay items, reviews
        Route::get('/scales', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'scales'])->name('scales');
        Route::post('/grades', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'storeGrade'])->name('grades.store');
        Route::put('/grades/{grade}', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'updateGrade'])->whereNumber('grade')->name('grades.update');
        Route::post('/grades/{grade}/steps', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'saveSteps'])->whereNumber('grade')->name('grades.steps');
        Route::get('/items', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'items'])->name('items');
        Route::post('/items', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'storeItem'])->name('items.store');
        Route::put('/items/{item}', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'updateItem'])->whereNumber('item')->name('items.update');
        Route::post('/items/assign', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'assignItem'])->name('items.assign');
        Route::delete('/staff-items/{id}', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'removeStaffItem'])->whereNumber('id')->name('staff-items.remove');
        Route::get('/reviews', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'reviews'])->name('reviews');
        Route::post('/reviews', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'reviewStore'])->name('reviews.store');
        Route::get('/reviews/{review}', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'reviewShow'])->whereNumber('review')->name('reviews.show');
        Route::post('/reviews/{review}/approve', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'reviewApprove'])->whereNumber('review')->name('reviews.approve');
        Route::post('/reviews/{review}/apply', [\App\Http\Controllers\Finance\PayrollStructureController::class, 'reviewApply'])->whereNumber('review')->name('reviews.apply');
    });

    // ===================================================================
    // SCHOOL-WIDE PAYMENT ANALYSIS
    // ===================================================================
    Route::get('/school-wide-payment-analysis/{termid_id}/{session_id}/{action?}/{format?}', 'App\Http\Controllers\AnalysisController@schoolWidePaymentAnalysis')
        ->name('school.wide.payment.analysis')
        ->where(['action' => 'view|download', 'format' => 'pdf|word']);

    // ===================================================================
    // STUDENT REPORTS (ViewStudent / ViewStudentReport)
    // ===================================================================
    Route::get('/viewstudent/{schoolclassid}/{termid}/{sessionid}', [ViewStudentController::class, 'show'])->name('viewstudent');

    Route::get('/studentreports', [ViewStudentReportController::class, 'index'])->name('studentreports.index');
    Route::get('/studentresult/{id}/{schoolclassid}/{sessionid}/{termid}', [ViewStudentReportController::class, 'studentresult'])->name('studentresult');
    Route::get('/student-reports/registered-classes', [ViewStudentReportController::class, 'registeredClasses'])->name('studentreports.registeredClasses');
    Route::get('/class-broadsheet/{schoolclassid}/{sessionid}/{termid}', [ViewStudentReportController::class, 'classBroadsheet'])->name('classbroadsheet');
    Route::match(['get', 'post'], '/studentreports/export-class-results-pdf', [ViewStudentReportController::class, 'exportClassResultsPdf'])->name('studentreports.exportClassResultsPdf');

    // Mock student reports
    Route::get('/studentmockreports', [ViewStudentMockReportController::class, 'index'])->name('studentmockreports.index');
    Route::post('studentmockreports/column-options', [ViewStudentMockReportController::class, 'getColumnOptions'])->name('studentmockreports.column-options');
    Route::post('studentmockreports/export-class-pdf', [ViewStudentMockReportController::class, 'exportClassMockResultsPdf'])->name('studentmockreports.exportClassMockResultsPdf');
    Route::get('studentmockreports/{id}/{schoolclassid}/{sessionid}/{termid}', [ViewStudentMockReportController::class, 'studentmockresult'])->name('studentmockreports.studentmockresult');
    Route::get('studentmockreports/{id}/{schoolclassid}/{sessionid}/{termid}/pdf', [ViewStudentMockReportController::class, 'exportStudentMockResultPdf'])->name('studentmockreports.exportStudentMockResultPdf');
    Route::get('studentmockreports/registered-classes', [ViewStudentMockReportController::class, 'registeredClasses'])->name('studentmockreports.registeredClasses');

    // ===================================================================
    // SUBJECT OPERATIONS
    // ===================================================================
    Route::get('/subjectoperation/snapshot/detail', [SubjectOperationController::class, 'getSnapshotDetail'])->name('subjectoperation.snapshot.detail');
    Route::get('/subjectoperation/registered-classes', [SubjectOperationController::class, 'registeredClasses'])->name('subjectoperation.registered-classes');
    Route::get('/subjectoperation/student-subject-counts', [SubjectOperationController::class, 'getStudentSubjectCounts'])->name('subjectoperation.student-subject-counts');

    Route::get('/subjects', [SubjectOperationController::class, 'index'])->name('subjects.index');
    Route::post('/subjectregistration', [SubjectOperationController::class, 'store'])->name('subjects.store');
    Route::get('/subjectoperation/subjectinfo/{id}/{schoolclassid}/{termid}/{sessionid}', [SubjectOperationController::class, 'subjectinfo'])->name('subjects.subjectinfo');

    Route::delete('/subjects/registered-classes', [SubjectOperationController::class, 'destroy'])->name('subjects.destroy');
    Route::get('/subjects/registered-classes', [SubjectOperationController::class, 'getRegisteredClasses'])->name('subjects.registered-classes');

    Route::post('/subjectregistration/destroy', [SubjectOperationController::class, 'destroy'])->name('subjectregistration.destroy');
    Route::post('/subjectregistration/batch', [SubjectOperationController::class, 'batchRegister'])->name('subjectregistration.batch');

    Route::get('/subjectoperation/archived', [SubjectOperationController::class, 'getArchivedRegistrations'])->name('subjectoperation.archived');
    Route::post('/subjectoperation/restore', [SubjectOperationController::class, 'restoreRegistration'])->name('subjectoperation.restore');

    Route::delete('/subjectoperation/archive/batch-delete', [SubjectOperationController::class, 'permanentlyDeleteArchiveBatch'])->name('subjectoperation.archive.batch-delete');
    Route::delete('/subjectoperation/archive/{archiveId}', [SubjectOperationController::class, 'permanentlyDeleteArchive'])->name('subjectoperation.archive.delete');
    Route::get('/school-information/get', [SubjectOperationController::class, 'getSchoolInformation'])->name('school.information.get');
    Route::resource('subjectoperation', SubjectOperationController::class);

    // Active school info API
    Route::get('/api/school-information/active', function () {
        $schoolInfo = App\Models\SchoolInformation::getActiveSchool();
        return response()->json([
            'success' => true,
            'data' => $schoolInfo ? [
                'school_name'    => $schoolInfo->school_name,
                'school_address' => $schoolInfo->school_address,
                'school_phone'   => $schoolInfo->school_phone,
                'school_email'   => $schoolInfo->school_email,
                'school_motto'   => $schoolInfo->school_motto,
                'school_website' => $schoolInfo->school_website,
                'logo_url'       => $schoolInfo->logo_url,
            ] : null,
        ]);
    })->name('api.school-information.active');

    // ===================================================================
    // RESULTS / PERSONALITY / BROADSHEETS / COMMENTS
    // ===================================================================
    Route::get('/viewresults/{id}/{schoolclassid}/{sessid}/{termid}', [StudentResultsController::class, 'viewresults']);

    Route::get('/studentpersonalityprofile/{id}/{schoolclassid}/{sessid}/{termid}', [StudentpersonalityprofileController::class, 'studentpersonalityprofile'])->name('myclass.studentpersonalityprofile');
    Route::post('save', [StudentpersonalityprofileController::class, 'save'])->name('studentpersonalityprofile.save');
    Route::get('/studentpersonalityprofile/data/{id}/{schoolclassid}/{sessionid}/{termid}', [StudentpersonalityprofileController::class, 'profileData'])->name('myclass.studentpersonalityprofile.data');

    Route::get('/classbroadsheet/{schoolclassid}/{sessionid}/{termid}', [ClassBroadsheetController::class, 'classBroadsheet'])->name('classbroadsheet.viewcomments');
    Route::patch('/classbroadsheet/{schoolclassid}/{sessionid}/{termid}/comments', [ClassBroadsheetController::class, 'updateComments'])->name('classbroadsheet.updateComments');
    Route::get('/classbroadsheet/past-comments/{studentId}', [ClassBroadsheetController::class, 'getPastComments']);

    // ===================================================================
    // COMPULSORY SUBJECTS
    // ===================================================================
    Route::prefix('compulsorysubjectclass')->group(function () {
        Route::get('/data', [CompulsorySubjectClassController::class, 'data'])->name('compulsorysubjectclass.data');
        Route::get('/stats', [CompulsorySubjectClassController::class, 'stats'])->name('compulsorysubjectclass.stats');
        Route::post('/bulk-destroy', [CompulsorySubjectClassController::class, 'deleteMultiple'])->name('compulsorysubjectclass.bulkDestroy');
        Route::get('/subjects-by-class', [CompulsorySubjectClassController::class, 'subjectsByClass'])->name('compulsorysubjectclass.subjectsByClass');
        Route::post('/update-pass-average', [CompulsorySubjectClassController::class, 'updatePassAverage'])->name('compulsorysubjectclass.updatePassAverage');
    });
    Route::resource('compulsorysubjectclass', CompulsorySubjectClassController::class);


    // ===================================================================
    // PRINCIPAL'S COMMENT
    // ===================================================================
    Route::get('principalscomment/data',  [PrincipalsCommentController::class, 'data'])->name('principalscomment.data');
    Route::get('principalscomment/stats', [PrincipalsCommentController::class, 'stats'])->name('principalscomment.stats');
    Route::resource('principalscomment', PrincipalsCommentController::class);

    Route::prefix('myprincipalscomment')->name('myprincipalscomment.')->group(function () {
        Route::get('/', [MyPrincipalsCommentController::class, 'index'])->name('index');
        Route::get('/broadsheet/{schoolclassid}/{sessionid}/{termid}', [MyPrincipalsCommentController::class, 'classBroadsheet'])->name('classbroadsheet');
        Route::post('/broadsheet/{schoolclassid}/{sessionid}/{termid}', [MyPrincipalsCommentController::class, 'updateComments'])->name('updateComments');
    });


    // ===================================================================
    // SUBJECT / MOCK VETTING
    // ===================================================================

    // Subject Vetting (Terminal)
    Route::get('subjectvetting/data',  [SubjectVettingController::class, 'data'])->name('subjectvetting.data');
    Route::get('subjectvetting/stats', [SubjectVettingController::class, 'stats'])->name('subjectvetting.stats');
    Route::post('subjectvetting/bulk-delete', [SubjectVettingController::class, 'bulkDelete'])->name('subjectvetting.bulkDelete');

    // AJAX search endpoints used by the terminal subject vetting page
    Route::get('api/subject-classes/search', [SubjectVettingController::class, 'searchSubjectClasses'])->name('api.subject-classes.search');
    Route::get('api/subject-classes/selected', [SubjectVettingController::class, 'getSelectedSubjectClasses'])->name('api.subject-classes.selected');

    Route::resource('subjectvetting', SubjectVettingController::class);

    Route::get('mocksubjectvetting/data',  [MockSubjectVettingController::class, 'data'])->name('mocksubjectvetting.data');
    Route::get('mocksubjectvetting/stats', [MockSubjectVettingController::class, 'stats'])->name('mocksubjectvetting.stats');
    Route::post('mocksubjectvetting/bulk-delete', [MockSubjectVettingController::class, 'bulkDelete'])->name('mocksubjectvetting.bulkDelete');

    // AJAX search endpoints for mock subject-classes
    Route::get('api/mock-subject-classes/search', [MockSubjectVettingController::class, 'searchSubjectClasses'])->name('api.mock-subject-classes.search');
    Route::get('api/mock-subject-classes/selected', [MockSubjectVettingController::class, 'getSelectedSubjectClasses'])->name('api.mock-subject-classes.selected');

    // Existing resource
    Route::resource('mocksubjectvetting', MockSubjectVettingController::class);


    // My Subject Vettings
    Route::get('/mysubjectvettings', [MySubjectVettingsController::class, 'index'])->name('mysubjectvettings.index');
    Route::get('/mysubjectvettings/classbroadsheet/{schoolclassid}/{subjectclassid}/{staffid}/{termid}/{sessionid}', [MySubjectVettingsController::class, 'classBroadsheet'])->name('mysubjectvettings.classbroadsheet');
    Route::post('/mysubjectvettings/bulk-vet', [MySubjectVettingsController::class, 'bulkVet'])->name('mysubjectvettings.bulk-vet');
    Route::put('/mysubjectvettings/{id}/status', [MySubjectVettingsController::class, 'updateStatus'])->whereNumber('id')->name('mysubjectvettings.status');

    Route::get('/mymocksubjectvettings', [MyMockSubjectVettingsController::class, 'index'])->name('mymocksubjectvettings.index');
    Route::get('/mymocksubjectvettings/classbroadsheet/{schoolclassid}/{subjectclassid}/{staffid}/{termid}/{sessionid}', [MyMockSubjectVettingsController::class, 'classBroadsheet'])->name('mymocksubjectvettings.classbroadsheet');
    Route::post('/mymocksubjectvettings/update-vetted-status', [MyMockSubjectVettingsController::class, 'updateVettedStatus'])->name('mymocksubjectvettings.update-vetted-status');
    Route::post('/mymocksubjectvettings/bulk-vet', [MyMockSubjectVettingsController::class, 'bulkVet'])->name('mymocksubjectvettings.bulk-vet');
    Route::put('/mymocksubjectvettings/{id}/status', [MyMockSubjectVettingsController::class, 'updateStatus'])->whereNumber('id')->name('mymocksubjectvettings.status');

    Route::post('/broadsheets/update-vetted-status', [MySubjectVettingsController::class, 'updateVettedStatus'])->name('broadsheets.update-vetted-status');

    // ===================================================================
    // STAFF IMAGE UPLOAD
    // ===================================================================
    Route::get('image-upload', [StaffImageUploadController::class, 'imageUpload'])->name('image.upload');
    Route::post('image-upload', [StaffImageUploadController::class, 'imageUploadPost'])->name('image.upload.post');

    // ===================================================================
    // EXAMS / CBT
    // ===================================================================
    Route::resource('exams', ExamController::class)->except(['show']);
    Route::delete('exams/bulk-destroy', [ExamController::class, 'bulkDestroy'])->name('exams.bulk-destroy');
    Route::get('exams/{exam}/students', [ExamController::class, 'showStudents'])->name('exams.students');
    Route::delete('exams/{exam}/students/{student}/attempt', [ExamController::class, 'deleteStudentAttempt'])->name('exams.student.attempt.delete');
    Route::get('exams/{exam}/students/{student}/answers', [ExamController::class, 'showStudentAnswers'])->name('exams.student.answers');
    Route::get('exams/{exam}/students/{student}/question-paper', [ExamController::class, 'generateQuestionPaperPdf'])->name('exams.student.question-paper');
    Route::get('exams/{exam}/analytics', [ExamController::class, 'analytics'])->name('exams.analytics');
    Route::get('exams/filtered-subjects', [ExamController::class, 'getFilteredSubjects'])->name('exams.filtered-subjects');
    Route::get('exams/subject-classes/{subjectTeacherId}', [ExamController::class, 'getClassesForSubject'])->name('exams.subject-classes');
    Route::get('/exams/{exam}/questions', [ExamController::class, 'getExamQuestions'])->name('exams.questions');
    Route::post('/exams/update-assessment-score', [ExamController::class, 'updateAssessmentScore'])->name('exams.update-assessment-score');
    Route::get('/exams/assessments/{examId}', [ExamController::class, 'getAssessments'])->name('exams.get-assessments');

    Route::get('/exams/transfer/subjects', [ExamController::class, 'showTransferSubjects'])->name('exams.transfer.subjects');
    Route::post('/exams/transfer/subjects', [ExamController::class, 'getTransferSubjects'])->name('exams.transfer.subjects.post');
    Route::get('/exams/transfer/scoresheet/{schoolclassid}/{subjectclassid}/{staffid}/{termid}/{sessionid}', [ExamController::class, 'showTransferScoresheet'])->name('exams.transfer.scoresheet');

    Route::get('/exams/assessments/for-subject/{subjectclassId}/{termId}/{sessionId}', [ExamController::class, 'getAssessmentsForSubject'])->name('exams.assessments.for-subject');
    Route::get('/exams/{exam}/generate-pdf/{student}', [ExamController::class, 'generateQuestionPaperPdf'])->name('exams.generate-pdf');

    // Question bank
    Route::get('/questions/get-exams', [QuestionController::class, 'getExamsForSelection'])->name('questions.getExams');
    Route::get('/questions/all-questions', [QuestionController::class, 'index'])->name('questions.all');
    Route::post('/questions/import', [QuestionController::class, 'import'])->name('questions.import');
    Route::post('/questions/export/pdf', [QuestionController::class, 'exportPdf'])->name('questions.export.pdf');
    Route::post('/questions/export/word', [QuestionController::class, 'exportWord'])->name('questions.export.word');
    Route::post('/questions/{question}/duplicate', [QuestionController::class, 'duplicate'])->name('questions.duplicate');
    Route::post('/questions/reorder', [QuestionController::class, 'reorder'])->name('questions.reorder');
    Route::post('/questions/bulk-update', [QuestionController::class, 'bulkUpdate'])->name('questions.bulk.update');
    Route::get('/questions/reusable/list', [QuestionController::class, 'getReusableQuestions'])->name('questions.reusable.list');
    Route::delete('/questions/bulk-destroy', [QuestionController::class, 'bulkDestroy'])->name('questions.bulk.destroy');
    Route::resource('questions', QuestionController::class);
    Route::get('/questions/{question}/details', [QuestionController::class, 'showDetails']);
    Route::get('/{question}/details', [QuestionController::class, 'details'])->name('questions.details');
    Route::get('/questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');

    Route::resource('cbt', CBTController::class);
    Route::get('/cbt/{examid}/takecbt', [CBTController::class, 'takeCBT'])->name('cbt.take');
    Route::post('/cbt/submit', [CBTController::class, 'submit'])->name('cbt.submit');

    Route::post('/admin/exams/{exam}/pause', [ExamPauseController::class, 'pause'])->name('admin.exams.pause');
    Route::post('/admin/exams/{exam}/resume', [ExamPauseController::class, 'resume'])->name('admin.exams.resume');
    Route::get('/api/exams/{exam}/status', [ExamPauseController::class, 'status'])->name('api.exams.status');

    Route::get('/debug-student-scores', [ViewStudentReportController::class, 'debugStudentScores']);

    // ===================================================================
    // BROADSHEET
    // ===================================================================
    Route::prefix('broadsheet')->name('broadsheet.')->group(function () {
        Route::get('/', [BroadsheetController::class, 'index'])->name('index');
        Route::post('/column-options', [BroadsheetController::class, 'getColumnOptions'])->name('column-options');
        Route::post('/student-preview', [BroadsheetController::class, 'getStudentPreview'])->name('student-preview');
        Route::match(['GET', 'POST'], '/web-view', [BroadsheetController::class, 'webView'])->name('web-view');
        Route::post('/export/pdf', [BroadsheetController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/export/excel', [BroadsheetController::class, 'exportExcel'])->name('export.excel');
        Route::match(['GET', 'POST'], '/student-list', [BroadsheetController::class, 'studentList'])->name('student-list');
    });
    Route::get('broadsheet/best-students', [BroadsheetController::class, 'bestStudents'])->name('broadsheet.best-students');
    Route::get('dashboard/best-students/print', [DashboardController::class, 'bestStudentsPrint'])->name('dashboard.best-students.print');

    Route::post('broadsheet/best-students', [BroadsheetController::class, 'bestStudentsReport'])->name('broadsheet.best-students.report');
    Route::post('/broadsheet/all-classes/web', [BroadsheetController::class, 'allClassesWebView'])->name('broadsheet.all-classes.web');
    Route::post('/broadsheet/all-classes/pdf', [BroadsheetController::class, 'allClassesExportPdf'])->name('broadsheet.all-classes.pdf');
    Route::get('/broadsheet/class-groups', [BroadsheetController::class, 'getClassGroups'])->name('broadsheet.class-groups');




     // ===================================================================
    // TIMETABLE
    // ===================================================================
    Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable.index');

    Route::get('/timetable/teacher', [TimetableController::class, 'teacherView'])->name('timetable.teacher');
    Route::get('/timetable/teacher/export', [TimetableController::class, 'exportTeacherTimetable'])->name('timetable.export-teacher');

    Route::post('/timetable/setup', [TimetableController::class, 'setup'])->name('timetable.setup');

    Route::post('/timetable/save-settings', [TimetableController::class, 'saveSettings'])->name('timetable.save-settings');
    Route::post('/timetable/rebuild-periods', [TimetableController::class, 'rebuildPeriodsFromAnchors'])->name('timetable.rebuild-periods-from-anchors');
    Route::post('/timetable/save-half-days', [TimetableController::class, 'saveHalfDays'])->name('timetable.save-half-days');

    Route::post('/timetable/save-constraints', [TimetableController::class, 'saveConstraints'])->name('timetable.save-constraints');

    Route::get('/timetable/get-setting/{settingId}', [TimetableController::class, 'getSetting'])->name('timetable.get-setting');
    Route::get('/timetable/get-grid/{settingId}', [TimetableController::class, 'getGrid'])->name('timetable.get-grid');
    Route::post('/timetable/save-slot', [TimetableController::class, 'saveSlot'])->name('timetable.save-slot');
    Route::post('/timetable/bulk-update', [TimetableController::class, 'bulkUpdateSlots'])->name('timetable.bulk-update');

    Route::get('/timetable/check-conflicts/{settingId}', [TimetableController::class, 'checkConflicts'])->name('timetable.check-conflicts');
    Route::get('/timetable/check-conflicts-scope', [TimetableController::class, 'checkConflictsScope'])->name('timetable.check-conflicts-scope');
    Route::post('/timetable/resolve-subject-spread', [TimetableController::class, 'resolveSubjectSpread'])->name('timetable.resolve-subject-spread');
    Route::post('/timetable/check-slot-conflict', [TimetableController::class, 'checkSlotConflict'])->name('timetable.check-slot-conflict');
    Route::post('/timetable/resolve-conflict', [TimetableController::class, 'resolveConflict'])->name('timetable.resolve-conflict');
    Route::post('/timetable/save-free-periods', [TimetableController::class, 'saveFreePeriods'])->name('timetable.save-free-periods');

    Route::post('/timetable/auto-generate', [TimetableController::class, 'autoGenerate'])->name('timetable.auto-generate');
    Route::post('/timetable/auto-generate-whole-school', [TimetableController::class, 'autoGenerateWholeSchool'])->name('timetable.auto-generate-whole-school');
    Route::post('/timetable/apply-generation-template', [TimetableController::class, 'applyGenerationTemplate'])->name('timetable.apply-generation-template');

    // Generation wizard + preview
    Route::get('/timetable/wizard-data', [TimetableController::class, 'getGenerationWizardData'])->name('timetable.wizard-data');
    Route::post('/timetable/preview-generation', [TimetableController::class, 'previewGeneration'])->name('timetable.preview-generation');

    Route::get('/timetable/teacher-assignments', [TimetableController::class, 'getTeacherAssignments'])->name('timetable.teacher-assignments');
    Route::get('/timetable/class-subjects', [TimetableController::class, 'getClassSubjects'])->name('timetable.class-subjects');

    Route::get('/timetable/export/{settingId}', [TimetableController::class, 'export'])->name('timetable.export');
    Route::get('/timetable/export-whole-school', [TimetableController::class, 'exportWholeSchool'])->name('timetable.export-whole-school');
    Route::get('/timetable/export-whole-school-web', [TimetableController::class, 'exportWholeSchoolWeb'])->name('timetable.export-whole-school-web');
    Route::get('/timetable/export-merged-grid', [TimetableController::class, 'exportMergedGrid'])->name('timetable.export-merged-grid');
    Route::get('/timetable/merged-grid-web', [TimetableController::class, 'mergedGridWeb'])->name('timetable.merged-grid-web');

    Route::post('/timetable/send-notifications', [TimetableController::class, 'sendNotifications'])->name('timetable.send-notifications');
    Route::post('/timetable/publish-and-notify', [TimetableController::class, 'publishAndNotify'])->name('timetable.publish-and-notify');

    Route::post('/timetable/publish/{settingId}', [TimetableController::class, 'publishSetting'])->name('timetable.publish-setting');
    Route::post('/timetable/unpublish/{settingId}', [TimetableController::class, 'unpublishSetting'])->name('timetable.unpublish-setting');

    Route::post('/timetable/clone', [TimetableController::class, 'cloneSetting'])->name('timetable.clone-setting');
    Route::delete('/timetable/delete/{settingId}', [TimetableController::class, 'deleteSetting'])->name('timetable.delete-setting');

    Route::post('/timetable/heartbeat/{id}', [TimetableController::class, 'heartbeat'])->name('timetable.heartbeat');
    Route::post('/timetable/release-editing/{id}', [TimetableController::class, 'releaseEditing'])->name('timetable.release-editing');

    Route::post('/timetable/request-substitute', [TimetableController::class, 'requestSubstitute'])->name('timetable.request-substitute');
    Route::post('/timetable/approve-substitute/{substituteId}', [TimetableController::class, 'approveSubstitute'])->name('timetable.approve-substitute');
    Route::get('/timetable/substitute-requests', [TimetableController::class, 'getSubstituteRequests'])->name('timetable.substitute-requests');
    Route::get('/timetable/available-substitutes', [TimetableController::class, 'getAvailableSubstitutes'])->name('timetable.available-substitutes');

    Route::post('/timetable/save-availability', [TimetableController::class, 'saveTeacherAvailability'])->name('timetable.save-availability');
    Route::get('/timetable/get-availability/{teacherId}', [TimetableController::class, 'getTeacherAvailability'])->name('timetable.get-availability');

    Route::get('/timetable/workload-dashboard', [TimetableController::class, 'workloadDashboard'])->name('timetable.workload-dashboard');



    // Saved generation runs
    Route::post('/timetable/runs/save',           [TimetableController::class, 'saveGenerationRun'])->name('timetable.runs.save');
    Route::get('/timetable/runs',                 [TimetableController::class, 'listGenerationRuns'])->name('timetable.runs.list');
    Route::post('/timetable/runs/compare',        [TimetableController::class, 'compareGenerationRuns'])->name('timetable.runs.compare');
    Route::get('/timetable/runs/{identifier}',    [TimetableController::class, 'showGenerationRun'])->name('timetable.runs.show');
    Route::post('/timetable/runs/{runId}/restore',[TimetableController::class, 'restoreGenerationRun'])->name('timetable.runs.restore');
    Route::get('/timetable/runs/{runId}/export',  [TimetableController::class, 'exportGenerationRun'])->name('timetable.runs.export');
    Route::delete('/timetable/runs/{runId}',      [TimetableController::class, 'deleteGenerationRun'])->name('timetable.runs.delete');

    // Period Allocation (reusable class x subject x periods/week presets)
    Route::get('/timetable/period-allocation/grid', [TimetableController::class, 'getPeriodAllocationGrid'])->name('timetable.periodAllocation.grid');
    Route::get('/timetable/period-allocation/sets', [TimetableController::class, 'listPeriodAllocationSets'])->name('timetable.periodAllocation.sets');
    Route::get('/timetable/period-allocation/sets/{setId}', [TimetableController::class, 'getPeriodAllocationSetDetail'])->name('timetable.periodAllocation.sets.show');
    Route::post('/timetable/period-allocation/sets', [TimetableController::class, 'savePeriodAllocationSet'])->name('timetable.periodAllocation.sets.save');
    Route::delete('/timetable/period-allocation/sets/{setId}', [TimetableController::class, 'deletePeriodAllocationSet'])->name('timetable.periodAllocation.sets.delete');




    // ===================================================================
    // TIMETABLE REPORTS
    // ===================================================================
    Route::prefix('timetable-reports')->name('timetable.reports.')->group(function () {
        Route::get('/', [TimetableReportController::class, 'index'])->name('index');
        Route::post('/generate', [TimetableReportController::class, 'generate'])->name('generate');
        Route::get('/{report}', [TimetableReportController::class, 'show'])->name('show');
        Route::get('/download/{report}', [TimetableReportController::class, 'download'])->name('download');
        Route::delete('/{report}', [TimetableReportController::class, 'destroy'])->name('destroy');
        Route::post('/schedule', [TimetableReportController::class, 'schedule'])->name('schedule');
    });

    // ===================================================================
    // ROOM MANAGEMENT
    // ===================================================================
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');

        Route::get('/rooms/mapping-counts', [RoomController::class, 'mappingCounts'])->name('rooms.mapping-counts');
        Route::get('/rooms/stats',          [RoomController::class, 'roomStats'])->name('rooms.stats');
        Route::get('/rooms/stats-detail/{roomId}', [RoomController::class, 'statsDetail'])->name('rooms.stats-detail');
        Route::get('/rooms/list-json',      [RoomController::class, 'listJson'])->name('rooms.list-json');  // ← must be above show/{id}
        Route::get('/rooms/mappings/{roomId}', [RoomController::class, 'mappings'])->name('rooms.mappings');
        Route::post('/rooms/mappings/{roomId}', [RoomController::class, 'storeMapping'])->name('rooms.mappings.store');
        Route::delete('/rooms/mappings/destroy/{mappingId}', [RoomController::class, 'destroyMapping'])->name('rooms.mappings.destroy');

        Route::post('/rooms/bulk/activate', [RoomController::class, 'bulkActivate'])->name('rooms.bulk.activate');
        Route::post('/rooms/bulk/destroy',  [RoomController::class, 'bulkDestroy'])->name('rooms.bulk.destroy');
        Route::post('/rooms/bulk/map',      [RoomController::class, 'bulkMap'])->name('rooms.bulk.map');

        Route::post('/rooms/store',       [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/show/{id}',    [RoomController::class, 'show'])->name('rooms.show');
        Route::post('/rooms/update/{id}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/destroy/{id}', [RoomController::class, 'destroy'])->name('rooms.destroy');
        Route::post('/rooms/book',              [RoomController::class, 'book'])->name('rooms.book');
        Route::post('/rooms/book/{roomId}',     [RoomController::class, 'book'])->name('rooms.book.room');
        Route::delete('/rooms/cancel-booking/{bookingId}', [RoomController::class, 'cancelBooking'])->name('rooms.cancel-booking');
        Route::post('/rooms/check-availability',[RoomController::class, 'checkAvailability'])->name('rooms.check-availability');





    // ===================================================================
    // LOOKUP ENDPOINTS (used by mapping modal + wizard panels)
    // ===================================================================
    Route::get('/api/classes-list', function () {
        $classes = \App\Models\Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name'])
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->get()
            ->map(fn($c) => [
                'id'    => $c->id,
                'label' => trim($c->schoolclass . ' ' . ($c->arm_name ?? '')),
            ]);
        return response()->json(['data' => $classes]);
    })->name('api.classes-list');

    Route::get('/api/subjects-list', function () {
        return response()->json([
            'data' => \App\Models\Subject::orderBy('subject')->get(['id', 'subject']),
        ]);
    })->name('api.subjects-list');

    Route::get('/api/sessions-list', function () {
        return response()->json([
            'data' => \App\Models\Schoolsession::orderByDesc('id')->get(['id', 'session']),
        ]);
    })->name('api.sessions-list');

    Route::get('/api/terms-list', function () {
        return response()->json([
            'data' => \App\Models\Schoolterm::orderBy('id')->get(['id', 'term']),
        ]);
    })->name('api.terms-list');

    // ===================================================================
    // EXAM TIMETABLE
    // ===================================================================
    Route::prefix('exam-timetable')->name('exam-timetable.')->group(function () {
        Route::get('/', [ExamTimetableController::class, 'index'])->name('index');
        Route::post('/', [ExamTimetableController::class, 'store'])->name('store');
        Route::get('/{examTimetable}', [ExamTimetableController::class, 'show'])->name('show');
        Route::put('/{examTimetable}', [ExamTimetableController::class, 'update'])->name('update');
        Route::delete('/{examTimetable}', [ExamTimetableController::class, 'destroy'])->name('destroy');
        Route::post('/{examTimetable}/slots', [ExamTimetableController::class, 'addSlot'])->name('add-slot');
        Route::delete('/slots/{slot}', [ExamTimetableController::class, 'removeSlot'])->name('remove-slot');
        Route::post('/{examTimetable}/publish', [ExamTimetableController::class, 'publish'])->name('publish');
        Route::get('/{examTimetable}/export', [ExamTimetableController::class, 'export'])->name('export');
    });

    // ===================================================================
    // HOLIDAYS
    // ===================================================================
    Route::prefix('holidays')->name('holidays.')->group(function () {
        Route::get('/', [HolidayController::class, 'index'])->name('index');
        Route::post('/', [HolidayController::class, 'store'])->name('store');
        Route::get('/{holiday}', [HolidayController::class, 'show'])->name('show');
        Route::put('/{holiday}', [HolidayController::class, 'update'])->name('update');
        Route::delete('/{holiday}', [HolidayController::class, 'destroy'])->name('destroy');
        Route::post('/{holiday}/apply', [HolidayController::class, 'applyToTimetable'])->name('apply');
    });

    // ===================================================================
    // PROMOTIONS
    // ===================================================================
    Route::prefix('promotions')->name('promotions.')->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->name('index');
        Route::get('/student-details/{studentId}/{schoolclassId}/{sessionId}/{termId}', [PromotionController::class, 'getStudentDetails'])->name('student.details');
        Route::put('/{studentId}', [PromotionController::class, 'update'])->name('update');
        Route::delete('/{studentId}', [PromotionController::class, 'destroy'])->name('destroy');
        Route::post('/bulk/promote', [PromotionController::class, 'bulkPromote'])->name('bulk.promote');
        Route::post('/advance-term', [PromotionController::class, 'advanceTerm'])->name('advance-term');
        Route::get('/student-list', [PromotionController::class, 'studentList'])->name('student-list');
        Route::get('/history', [PromotionController::class, 'history'])->name('history');
        Route::post('/history/{batchId}/revert', [PromotionController::class, 'revertBatch'])->name('history.revert');
        Route::post('/{studentId}/clear-decision', [PromotionController::class, 'clearDecision'])->name('clear-decision');
    });

    Route::prefix('promotion-settings')->group(function () {
        Route::get('/', [PromotionSettingController::class, 'index'])->name('promotion-settings.index');
        Route::post('/', [PromotionSettingController::class, 'store'])->name('promotion-settings.store');
        Route::put('/{id}', [PromotionSettingController::class, 'update'])->name('promotion-settings.update');
        Route::delete('/{id}', [PromotionSettingController::class, 'destroy'])->name('promotion-settings.destroy');
        Route::post('/{id}/toggle-active', [PromotionSettingController::class, 'toggleActive'])->name('promotion-settings.toggle-active');
        Route::get('/class-promotion-data', [PromotionSettingController::class, 'getClassPromotionData'])->name('promotion-settings.class-data');
        Route::get('/subjects-by-class', [PromotionSettingController::class, 'subjectsByClass'])->name('promotion-settings.subjects-by-class');
        Route::get('/compulsory-by-class', [PromotionSettingController::class, 'compulsoryByClass'])->name('promotion-settings.compulsory-by-class');
    });

    Route::prefix('promotion-templates')->group(function () {
        Route::get('/', [PromotionRuleTemplateController::class, 'index'])->name('promotion.templates.index');
        Route::get('/create', [PromotionRuleTemplateController::class, 'create'])->name('promotion.templates.create');
        Route::post('/', [PromotionRuleTemplateController::class, 'store'])->name('promotion.templates.store');
        Route::get('/{id}/edit', [PromotionRuleTemplateController::class, 'edit'])->name('promotion.templates.edit');
        Route::put('/{id}', [PromotionRuleTemplateController::class, 'update'])->name('promotion.templates.update');
        Route::delete('/{id}', [PromotionRuleTemplateController::class, 'destroy'])->name('promotion.templates.destroy');
        Route::post('/{id}/toggle-active', [PromotionRuleTemplateController::class, 'toggleActive'])->name('promotion.templates.toggle-active');
        Route::get('/{id}/load-for-class', [PromotionRuleTemplateController::class, 'loadForClass'])->name('promotion.templates.load-for-class');
    });

    // ===================================================================
    // ATTENDANCE
    // ===================================================================
    Route::get('/attendance/my-classes', [AttendanceController::class, 'myClasses'])->name('attendance.my-classes');
    Route::get('/attendance/register/{classId}/{termId}/{sessionId}', [AttendanceController::class, 'register'])->name('attendance.register');
    Route::post('/attendance/save', [AttendanceController::class, 'save'])->name('attendance.save');
    Route::post('/attendance/save-single', [AttendanceController::class, 'saveSingle'])->name('attendance.save-single');
    Route::post('/attendance/mark-all-present', [AttendanceController::class, 'markAllPresent'])->name('attendance.mark-all-present');
    Route::get('/attendance/student/{studentId}/{classId}/{termId}/{sessionId}', [AttendanceController::class, 'studentReport'])->name('attendance.student-report');
    Route::get('/attendance/class-summary/{classId}/{termId}/{sessionId}', [AttendanceController::class, 'classSummary'])->name('attendance.class-summary');
    Route::get('/attendance/class-history/{classId}/{termId}/{sessionId}', [AttendanceController::class, 'classHistory'])->name('attendance.class-history');

    Route::get('/attendance/settings', [AttendanceSettingController::class, 'index'])->name('attendance.settings');
    Route::put('/attendance/settings/{id}', [AttendanceSettingController::class, 'update'])->name('attendance.settings.update');
    Route::post('/attendance/settings', [AttendanceSettingController::class, 'store'])->name('attendance.settings.store');
    Route::delete('/attendance/settings/{id}', [AttendanceSettingController::class, 'destroy'])->name('attendance.settings.destroy');
    Route::get('/attendance/holidays', [AttendanceSettingController::class, 'holidays'])->name('attendance.holidays');
    Route::post('/attendance/holidays', [AttendanceSettingController::class, 'storeHoliday'])->name('attendance.holidays.store');
    Route::delete('/attendance/holidays/{id}', [AttendanceSettingController::class, 'destroyHoliday'])->name('attendance.holidays.destroy');
    Route::get('/attendance/school-report', [AttendanceSettingController::class, 'schoolReport'])->name('attendance.school-report');

    // Device mappings + staff attendance
    Route::prefix('attendance')->group(function () {
        Route::get('device-mappings/search', [DeviceUserMappingController::class, 'search'])->name('device-mappings.search');
        Route::get('device-mappings/unmapped', [DeviceUserMappingController::class, 'unmapped'])->name('device-mappings.unmapped');
        Route::post('device-mappings/quick-assign', [DeviceUserMappingController::class, 'quickAssign'])->name('device-mappings.quick-assign');
        Route::post('device-mappings/bulk-import', [DeviceUserMappingController::class, 'bulkImport'])->name('device-mappings.bulk-import');
        Route::post('device-mappings/bulk-manual', [DeviceUserMappingController::class, 'bulkManualAssign'])->name('device-mappings.bulk-manual');
        Route::resource('device-mappings', DeviceUserMappingController::class)
            ->except(['show'])
            ->whereNumber('device_mapping');

        Route::post('staff/outage', [StaffAttendanceController::class, 'storeOutage'])->name('staff-attendance.outage.store');
        Route::delete('staff/outage/{id}', [StaffAttendanceController::class, 'destroyOutage'])->name('staff-attendance.outage.destroy');

        Route::get('staff-attendance/time-settings', 'App\Http\Controllers\StaffAttendanceTimeSettingController@edit')->name('staff-attendance.time-settings.edit');
        Route::post('staff-attendance/time-settings', 'App\Http\Controllers\StaffAttendanceTimeSettingController@update')->name('staff-attendance.time-settings.update');

        Route::get('staff-attendance/export', [StaffAttendanceController::class, 'exportExcel'])->name('staff-attendance.export');

        Route::get('staff-attendance', [StaffAttendanceController::class, 'index'])->name('staff-attendance.index');
        Route::get('staff-attendance/{staffId}', [StaffAttendanceController::class, 'report'])
            ->whereNumber('staffId')
            ->name('staff-attendance.report');

        Route::get('live-feed', [LiveAttendanceController::class, 'feed'])->name('attendance.live-feed');
    });

    // ===================================================================
    // TRANSCRIPT
    // ===================================================================
    Route::prefix('transcript')->name('transcript.')->group(function () {
        Route::get('/', [TranscriptController::class, 'index'])->name('index');
        Route::post('/search', [TranscriptController::class, 'searchStudents'])->name('search');
        Route::post('/preview', [TranscriptController::class, 'preview'])->name('preview');
        Route::post('/pdf', [TranscriptController::class, 'exportPdf'])->name('pdf');
    });

    // ===================================================================
    // ADMIN SCORE ENTRY
    // ===================================================================
    Route::prefix('admin')->name('admin.')->group(function () {
        // Database backups
        Route::prefix('backups')->name('backups.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('index');
            Route::post('/run', [\App\Http\Controllers\Admin\BackupController::class, 'run'])->name('run');
            Route::post('/settings', [\App\Http\Controllers\Admin\BackupController::class, 'saveSettings'])->name('settings');
            Route::get('/{backup}/download', [\App\Http\Controllers\Admin\BackupController::class, 'download'])->whereNumber('backup')->name('download');
            Route::delete('/{backup}', [\App\Http\Controllers\Admin\BackupController::class, 'destroy'])->whereNumber('backup')->name('destroy');
        });
        Route::prefix('score-entry')->name('score-entry.')->group(function () {
            Route::get('/', [AdminScoreEntryController::class, 'index'])->name('index');
            Route::get('/lock-management', [AdminScoreEntryController::class, 'lockManagement'])->name('lock-management');

            Route::get('/scoresheets-list', [AdminScoreEntryController::class, 'getScoresheetsList'])->name('scoresheets-list');
            Route::post('/bulk-lock-management', [AdminScoreEntryController::class, 'bulkLockManagement'])->name('bulk-lock-management');

            Route::post('/lock-scoresheet', [AdminScoreEntryController::class, 'lockScoresheet'])->name('lock-scoresheet');
            Route::post('/unlock-scoresheet', [AdminScoreEntryController::class, 'unlockScoresheet'])->name('unlock-scoresheet');
            Route::post('/lock-batch', [AdminScoreEntryController::class, 'lockBatchScoresheets'])->name('lock-batch');
            Route::post('/unlock-batch', [AdminScoreEntryController::class, 'unlockBatchScoresheets'])->name('unlock-batch');
            Route::post('/disable-teacher-editing', [AdminScoreEntryController::class, 'disableTeacherEditing'])->name('disable-teacher-editing');
            Route::post('/enable-teacher-editing', [AdminScoreEntryController::class, 'enableTeacherEditing'])->name('enable-teacher-editing');

            Route::get('/lock-status', [AdminScoreEntryController::class, 'getLockStatus'])->name('lock-status');

            Route::get('/scoresheet/{subjectclassId}/{teacherId}/{termId}/{sessionId}/{type?}', [AdminScoreEntryController::class, 'showScoresheet'])
                ->name('scoresheet')
                ->where('type', 'terminal|mock');

            Route::post('/single-update', [AdminScoreEntryController::class, 'singleUpdate'])->name('single-update');
            Route::post('/bulk-update', [AdminScoreEntryController::class, 'bulkUpdate'])->name('bulk-update');
            Route::delete('/destroy', [AdminScoreEntryController::class, 'destroy'])->name('destroy');
            Route::get('/results', [AdminScoreEntryController::class, 'results'])->name('results');
            Route::post('/bulk-export', [AdminScoreEntryController::class, 'bulkExport'])->name('bulk-export');
            Route::post('/bulk-export-pdf', [AdminScoreEntryController::class, 'bulkExportPdf'])->name('bulk-export-pdf');

            Route::get('/broadsheet-preview', [AdminScoreEntryController::class, 'broadsheetPreview'])->name('broadsheet-preview');

            Route::post('/mock-single-update', [AdminScoreEntryController::class, 'mockSingleUpdate'])->name('mock-single-update');
            Route::post('/mock-bulk-update', [AdminScoreEntryController::class, 'mockBulkUpdate'])->name('mock-bulk-update');

            Route::get('/download-marks-sheet', [AdminScoreEntryController::class, 'downloadMarksSheet'])->name('download-marks-sheet');
            Route::get('/download-scores-pdf', [AdminScoreEntryController::class, 'downloadScoresPdf'])->name('download-scores-pdf');
            Route::get('/export', [AdminScoreEntryController::class, 'export'])->name('export');
            Route::post('/import', [AdminScoreEntryController::class, 'import'])->name('import');

            Route::post('/grade-for-score', [AdminScoreEntryController::class, 'calculateGradeForScore'])->name('grade-for-score');
            Route::post('/update-arm-positions', [AdminScoreEntryController::class, 'updateAllArmPositions'])->name('update-arm-positions');

            Route::get('/student-result-manager', [AdminScoreEntryController::class, 'studentResultManager'])->name('student-result-manager');
            Route::post('/get-student-results', [AdminScoreEntryController::class, 'getStudentResults'])->name('get-student-results');
            Route::post('/update-student-subject-score', [AdminScoreEntryController::class, 'updateStudentSubjectScore'])->name('update-student-subject-score');
            Route::post('/bulk-update-student-scores', [AdminScoreEntryController::class, 'bulkUpdateStudentScores'])->name('bulk-update-student-scores');
        });
    });

    // ===================================================================
    // SPOTLIGHT SEARCH
    // ===================================================================
    Route::get('/api/search', [SearchController::class, 'search'])->name('api.search');
});
// ===================================================================
// PARENT PORTAL
// ===================================================================
// Password reset by SMS code (guests)
Route::prefix('parent')->name('parent.')->group(function () {
    Route::get('/forgot-password', [\App\Http\Controllers\Auth\ParentPasswordController::class, 'requestForm'])->name('forgot');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\ParentPasswordController::class, 'sendCode'])->middleware('throttle:5,10')->name('forgot.send');
    Route::get('/reset-password', [\App\Http\Controllers\Auth\ParentPasswordController::class, 'resetForm'])->name('reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\ParentPasswordController::class, 'reset'])->middleware('throttle:10,10')->name('reset.update');
});

Route::middleware('auth')->group(function () {
    // Change password (also where temporary passwords are replaced)
    Route::get('/account/password', [\App\Http\Controllers\ParentPortalController::class, 'passwordForm'])->name('parent.password');
    Route::put('/account/password', [\App\Http\Controllers\ParentPortalController::class, 'passwordUpdate'])->name('parent.password.update');

    Route::middleware('role:Parent')->prefix('parent')->name('parent.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ParentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/child/{student}/results', [\App\Http\Controllers\ParentPortalController::class, 'results'])->whereNumber('student')->name('results');
        Route::get('/child/{student}/report-card/{session}/{term}/{class}', [\App\Http\Controllers\ParentPortalController::class, 'reportCard'])
            ->whereNumber(['student', 'session', 'term', 'class'])->name('report-card');
        Route::get('/child/{student}/fees', [\App\Http\Controllers\ParentPortalController::class, 'fees'])->whereNumber('student')->name('fees');
        Route::get('/child/{student}/pay', [OnlineFeeController::class, 'parentPay'])->whereNumber('student')->name('pay');
        Route::get('/child/{student}/attendance', [\App\Http\Controllers\ParentPortalController::class, 'attendance'])->whereNumber('student')->name('attendance');
        Route::get('/child/{student}/timetable', [\App\Http\Controllers\ParentPortalController::class, 'timetable'])->whereNumber('student')->name('timetable');
    });

    // Admin: parent accounts
    Route::prefix('parent-accounts')->name('parent-accounts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ParentAccountController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\ParentAccountController::class, 'store'])->name('store');
        Route::post('/sync', [\App\Http\Controllers\ParentAccountController::class, 'sync'])->name('sync');
        Route::post('/send-all', [\App\Http\Controllers\ParentAccountController::class, 'sendAll'])->name('send-all');
        Route::post('/{user}/send', [\App\Http\Controllers\ParentAccountController::class, 'sendCredentials'])->whereNumber('user')->name('send');
        Route::post('/{user}/link', [\App\Http\Controllers\ParentAccountController::class, 'link'])->whereNumber('user')->name('link');
        Route::delete('/{user}/link/{student}', [\App\Http\Controllers\ParentAccountController::class, 'unlink'])->whereNumber(['user', 'student'])->name('unlink');
        Route::post('/{user}/toggle', [\App\Http\Controllers\ParentAccountController::class, 'toggle'])->whereNumber('user')->name('toggle');
    });
});

// ===================================================================
// FEE INSTALMENT PLANS
// ===================================================================
Route::middleware('auth')->prefix('instalment-plans')->name('instalment-plans.')->group(function () {
    Route::get('/', [\App\Http\Controllers\InstalmentPlanController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\InstalmentPlanController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\InstalmentPlanController::class, 'store'])->name('store');
    Route::get('/{plan}', [\App\Http\Controllers\InstalmentPlanController::class, 'show'])->whereNumber('plan')->name('show');
    Route::get('/{plan}/edit', [\App\Http\Controllers\InstalmentPlanController::class, 'edit'])->whereNumber('plan')->name('edit');
    Route::put('/{plan}', [\App\Http\Controllers\InstalmentPlanController::class, 'update'])->whereNumber('plan')->name('update');
    Route::delete('/{plan}', [\App\Http\Controllers\InstalmentPlanController::class, 'destroy'])->whereNumber('plan')->name('destroy');
    Route::post('/{plan}/toggle', [\App\Http\Controllers\InstalmentPlanController::class, 'toggle'])->whereNumber('plan')->name('toggle');
    Route::get('/{plan}/students', [\App\Http\Controllers\InstalmentPlanController::class, 'classStudents'])->whereNumber('plan')->name('students');
    Route::post('/{plan}/assign', [\App\Http\Controllers\InstalmentPlanController::class, 'assign'])->whereNumber('plan')->name('assign');
    Route::delete('/{plan}/students/{student}', [\App\Http\Controllers\InstalmentPlanController::class, 'unassign'])->whereNumber(['plan', 'student'])->name('unassign');
});

// ===================================================================
// REPORT CARD APPROVAL
// ===================================================================
Route::middleware('auth')->prefix('report-approvals')->name('report-approvals.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ReportApprovalController::class, 'index'])->name('index');
    Route::post('/settings', [\App\Http\Controllers\ReportApprovalController::class, 'settings'])->name('settings');
    Route::post('/approve-all', [\App\Http\Controllers\ReportApprovalController::class, 'approveAll'])->name('approve-all');
    Route::get('/class/{class}/{term}/{session}', [\App\Http\Controllers\ReportApprovalController::class, 'open'])->whereNumber(['class', 'term', 'session'])->name('open');
    Route::get('/{approval}', [\App\Http\Controllers\ReportApprovalController::class, 'show'])->whereNumber('approval')->name('show');
    Route::get('/{approval}/preview/{student}', [\App\Http\Controllers\ReportApprovalController::class, 'preview'])->whereNumber(['approval', 'student'])->name('preview');
    Route::post('/{approval}/submit', [\App\Http\Controllers\ReportApprovalController::class, 'submit'])->whereNumber('approval')->name('submit');
    Route::post('/{approval}/approve', [\App\Http\Controllers\ReportApprovalController::class, 'approve'])->whereNumber('approval')->name('approve');
    Route::post('/{approval}/return', [\App\Http\Controllers\ReportApprovalController::class, 'returnBack'])->whereNumber('approval')->name('return');
    Route::post('/{approval}/reopen', [\App\Http\Controllers\ReportApprovalController::class, 'reopen'])->whereNumber('approval')->name('reopen');
});

// ===================================================================
// MANAGEMENT DASHBOARD
// ===================================================================
Route::middleware('auth')->group(function () {
    Route::get('/management', [\App\Http\Controllers\ManagementDashboardController::class, 'index'])->name('management.dashboard');
    Route::get('/management/fee-status', [\App\Http\Controllers\ManagementDashboardController::class, 'feeStatus'])->name('management.fee-status');
});

// ===================================================================
// CLUBS & SPORTS MEMBERSHIP / SCHOOL HOUSES
// ===================================================================
Route::middleware('auth')->group(function () {
    Route::get('/activities/student', [\App\Http\Controllers\ActivityController::class, 'student'])->name('activities.student');
    Route::post('/activities/student', [\App\Http\Controllers\ActivityController::class, 'saveStudent'])->name('activities.student.save');
    Route::get('/activities/students/search', [\App\Http\Controllers\ActivityController::class, 'searchStudents'])->name('activities.students.search');
    Route::post('/activities/sport/{id}/teams', [\App\Http\Controllers\ActivityController::class, 'addTeam'])->whereNumber('id')->name('activities.team');

    Route::prefix('activities/{type}')->whereIn('type', ['club', 'sport'])->name('activities.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ActivityController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\ActivityController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{id}/students', [\App\Http\Controllers\ActivityController::class, 'classStudents'])->whereNumber('id')->name('class-students');
        Route::get('/{id}/export', [\App\Http\Controllers\ActivityController::class, 'export'])->whereNumber('id')->name('export');
        Route::post('/{id}/members', [\App\Http\Controllers\ActivityController::class, 'add'])->whereNumber('id')->name('add');
        Route::put('/{id}/details', [\App\Http\Controllers\ActivityController::class, 'updateDetails'])->whereNumber('id')->name('details');
        Route::put('/member/{member}', [\App\Http\Controllers\ActivityController::class, 'updateMember'])->whereNumber('member')->name('member.update');
        Route::delete('/member/{member}', [\App\Http\Controllers\ActivityController::class, 'removeMember'])->whereNumber('member')->name('member.remove');
    });

    Route::prefix('houses')->name('houses.')->group(function () {
        Route::get('/', [\App\Http\Controllers\HouseController::class, 'index'])->name('index');
        Route::get('/students', [\App\Http\Controllers\HouseController::class, 'classStudents'])->name('class-students');
        Route::post('/auto/preview', [\App\Http\Controllers\HouseController::class, 'autoPreview'])->name('auto.preview');
        Route::post('/auto/apply', [\App\Http\Controllers\HouseController::class, 'autoApply'])->name('auto.apply');
        Route::post('/points', [\App\Http\Controllers\HouseController::class, 'award'])->name('points.award');
        Route::delete('/points/{point}', [\App\Http\Controllers\HouseController::class, 'deletePoint'])->whereNumber('point')->name('points.delete');
        Route::post('/student/{student}/role', [\App\Http\Controllers\HouseController::class, 'role'])->whereNumber('student')->name('role');
        Route::post('/student/{student}/move', [\App\Http\Controllers\HouseController::class, 'move'])->whereNumber('student')->name('move');
        Route::get('/{house}', [\App\Http\Controllers\HouseController::class, 'show'])->whereNumber('house')->name('show');
        Route::post('/{house}/assign', [\App\Http\Controllers\HouseController::class, 'assign'])->whereNumber('house')->name('assign');
        Route::put('/{house}/details', [\App\Http\Controllers\HouseController::class, 'details'])->whereNumber('house')->name('details');
    });
});

// ===================================================================
// STAFF SELF-SERVICE PAY + PUBLIC DOCUMENT CHECKS
// ===================================================================
Route::middleware('auth')->prefix('my-pay')->name('my-pay.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Finance\MyPayController::class, 'index'])->name('index');
    Route::get('/payslip/{run}', [\App\Http\Controllers\Finance\MyPayController::class, 'payslip'])->whereNumber('run')->name('payslip');
    Route::get('/payslip/{run}/pdf', [\App\Http\Controllers\Finance\MyPayController::class, 'payslipPdf'])->whereNumber('run')->name('payslip.pdf');
    Route::get('/tax', [\App\Http\Controllers\Finance\MyPayController::class, 'tax'])->name('tax');
    Route::get('/tax-certificate', [\App\Http\Controllers\Finance\MyPayController::class, 'certificate'])->name('certificate');
    Route::get('/pension', [\App\Http\Controllers\Finance\MyPayController::class, 'pension'])->name('pension');
    Route::get('/deductions', [\App\Http\Controllers\Finance\MyPayController::class, 'deductions'])->name('deductions');
    Route::get('/statement', [\App\Http\Controllers\Finance\MyPayController::class, 'statement'])->name('statement');
});
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/verify/payslip/{code}', [\App\Http\Controllers\PayslipVerifyController::class, 'payslip'])->name('verify.payslip');
    Route::get('/verify/tax-certificate/{staff}/{year}/{code}', [\App\Http\Controllers\PayslipVerifyController::class, 'certificate'])->whereNumber(['staff', 'year'])->name('verify.tax-certificate');
});

// ===================================================================
// STAFF ACTIVITY LOG, WHO'S ONLINE, STAFF LEAVE
// ===================================================================
Route::middleware('auth')->group(function () {
    Route::get('/activity-log', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity.index');
    Route::get('/activity-log/export', [\App\Http\Controllers\ActivityLogController::class, 'export'])->name('activity.export');
    Route::get('/online-staff', [\App\Http\Controllers\ActivityLogController::class, 'online'])->name('online-staff.index');
    Route::get('/online-staff/count', [\App\Http\Controllers\ActivityLogController::class, 'onlineCount'])->name('online-staff.count');

    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/', [\App\Http\Controllers\LeaveController::class, 'index'])->name('index');
        Route::get('/board', [\App\Http\Controllers\LeaveBoardController::class, 'index'])->name('board');
        Route::get('/records/export', [\App\Http\Controllers\LeaveController::class, 'exportRecords'])->name('records.export');
        Route::get('/balances', [\App\Http\Controllers\LeaveController::class, 'hrBalances'])->name('balances');
        Route::post('/', [\App\Http\Controllers\LeaveController::class, 'store'])->name('store');
        Route::post('/{id}/cancel', [\App\Http\Controllers\LeaveController::class, 'cancel'])->whereNumber('id')->name('cancel');
        Route::get('/approvals', [\App\Http\Controllers\LeaveController::class, 'approvals'])->name('approvals');
        Route::post('/{id}/act', [\App\Http\Controllers\LeaveController::class, 'act'])->whereNumber('id')->name('act');
        Route::get('/{id}/document', [\App\Http\Controllers\LeaveController::class, 'attachment'])->whereNumber('id')->name('attachment');
        Route::get('/records', [\App\Http\Controllers\LeaveController::class, 'records'])->name('records');
        Route::post('/types', [\App\Http\Controllers\LeaveController::class, 'storeType'])->name('types.store');
        Route::put('/types/{id}', [\App\Http\Controllers\LeaveController::class, 'updateType'])->whereNumber('id')->name('types.update');
        Route::post('/adjust', [\App\Http\Controllers\LeaveController::class, 'adjust'])->name('adjust');
        Route::post('/{id}/resume', [\App\Http\Controllers\LeaveController::class, 'resume'])->whereNumber('id')->name('resume');
        Route::post('/{id}/mark-resumed', [\App\Http\Controllers\LeaveController::class, 'markResumed'])->whereNumber('id')->name('mark-resumed');
        Route::post('/{id}/extend', [\App\Http\Controllers\LeaveController::class, 'extend'])->whereNumber('id')->name('extend');
        Route::post('/reminders', [\App\Http\Controllers\LeaveController::class, 'saveReminders'])->name('reminders');
    });

    // Student leave of absence: student/parent apply; class teacher recommends, principal approves.
    Route::prefix('student-leave')->name('student-leave.')->group(function () {
        Route::get('/', [\App\Http\Controllers\StudentLeaveController::class, 'mine'])->name('mine');
        Route::post('/', [\App\Http\Controllers\StudentLeaveController::class, 'store'])->name('store');
        Route::post('/{leave}/cancel', [\App\Http\Controllers\StudentLeaveController::class, 'cancel'])->whereNumber('leave')->name('cancel');
        Route::get('/approvals', [\App\Http\Controllers\StudentLeaveController::class, 'approvals'])->name('approvals');
        Route::post('/{leave}/act', [\App\Http\Controllers\StudentLeaveController::class, 'act'])->whereNumber('leave')->name('act');
        Route::get('/records', [\App\Http\Controllers\StudentLeaveController::class, 'records'])->name('records');
        Route::get('/records/export', [\App\Http\Controllers\StudentLeaveController::class, 'exportRecords'])->name('records.export');
        Route::get('/{leave}/document', [\App\Http\Controllers\StudentLeaveController::class, 'attachment'])->whereNumber('leave')->name('attachment');
    });

    // School calendar (authenticated view for everyone; management gated in-controller).
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CalendarController::class, 'index'])->name('index');
        Route::get('/event/{event}', [\App\Http\Controllers\CalendarController::class, 'show'])->whereNumber('event')->name('show');
        Route::post('/', [\App\Http\Controllers\CalendarController::class, 'store'])->name('store');
        Route::put('/{event}', [\App\Http\Controllers\CalendarController::class, 'update'])->whereNumber('event')->name('update');
        Route::delete('/{event}', [\App\Http\Controllers\CalendarController::class, 'destroy'])->whereNumber('event')->name('destroy');
        Route::post('/{event}/rsvp', [\App\Http\Controllers\CalendarController::class, 'rsvp'])->whereNumber('event')->name('rsvp');
        Route::post('/{event}/attachments', [\App\Http\Controllers\CalendarController::class, 'uploadAttachment'])->whereNumber('event')->name('attachments.store');
        Route::get('/attachments/{attachment}', [\App\Http\Controllers\CalendarController::class, 'attachment'])->whereNumber('attachment')->name('attachment');
        Route::delete('/attachments/{attachment}', [\App\Http\Controllers\CalendarController::class, 'deleteAttachment'])->whereNumber('attachment')->name('attachments.destroy');
        Route::post('/categories', [\App\Http\Controllers\CalendarController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [\App\Http\Controllers\CalendarController::class, 'updateCategory'])->whereNumber('category')->name('categories.update');
        Route::delete('/categories/{category}', [\App\Http\Controllers\CalendarController::class, 'destroyCategory'])->whereNumber('category')->name('categories.destroy');
        Route::post('/sync-fees', [\App\Http\Controllers\CalendarController::class, 'syncFees'])->name('sync-fees');
    });

    // Certificates (confidential) — templates, generation, approval, audit.
    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CertificateController::class, 'index'])->name('index');
        Route::get('/generate', [\App\Http\Controllers\Admin\CertificateController::class, 'generate'])->name('generate');
        Route::get('/students', [\App\Http\Controllers\Admin\CertificateController::class, 'students'])->name('students');
        Route::post('/issue', [\App\Http\Controllers\Admin\CertificateController::class, 'issue'])->name('issue');
        Route::get('/logs', [\App\Http\Controllers\Admin\CertificateController::class, 'logs'])->name('logs');

        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'store'])->name('store');
            Route::get('/{template}/edit', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'edit'])->whereNumber('template')->name('edit');
            Route::post('/{template}', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'update'])->whereNumber('template')->name('update');
            Route::delete('/{template}', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'destroy'])->whereNumber('template')->name('destroy');
            Route::post('/{template}/duplicate', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'duplicate'])->whereNumber('template')->name('duplicate');
        });
        Route::post('/assets', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'uploadAsset'])->name('assets.upload');

        Route::get('/{certificate}', [\App\Http\Controllers\Admin\CertificateController::class, 'show'])->whereNumber('certificate')->name('show');
        Route::get('/{certificate}/print', [\App\Http\Controllers\Admin\CertificateController::class, 'print'])->whereNumber('certificate')->name('print');
        Route::post('/{certificate}/generate-hit', [\App\Http\Controllers\Admin\CertificateController::class, 'generateHit'])->whereNumber('certificate')->name('generate-hit');
        Route::post('/{certificate}/rendered', [\App\Http\Controllers\Admin\CertificateController::class, 'storeRendered'])->whereNumber('certificate')->name('rendered');
        Route::get('/{certificate}/download', [\App\Http\Controllers\Admin\CertificateController::class, 'download'])->whereNumber('certificate')->name('download');
        Route::post('/{certificate}/approve', [\App\Http\Controllers\Admin\CertificateController::class, 'approve'])->whereNumber('certificate')->name('approve');
        Route::post('/{certificate}/revoke', [\App\Http\Controllers\Admin\CertificateController::class, 'revoke'])->whereNumber('certificate')->name('revoke');
    });
});

// Finance operations: payouts, loans, cooperative, expenses, budgets, assets, general ledger
require __DIR__ . '/finance.php';

// E-learning (LMS): courses, lessons, coursework, live classes, learner & parent views
require __DIR__ . '/lms.php';

// Curriculum: topics, scheme of work, coverage, teacher board, class reps
require __DIR__ . '/curriculum.php';

// Exams (Phase 4): paper building, vetting, topic tagging, scores, bank, coverage
require __DIR__ . '/exam.php';

// Broadsheet ranking settings (unofficial best-student ranking)
require __DIR__ . '/broadsheet.php';
