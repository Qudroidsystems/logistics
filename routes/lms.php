<?php

use App\Http\Controllers\Lms\AssignmentController;
use App\Http\Controllers\Lms\ContentController;
use App\Http\Controllers\Lms\CourseController;
use App\Http\Controllers\Lms\EngagementController;
use App\Http\Controllers\Lms\EnrollmentController;
use App\Http\Controllers\Lms\GradebookController;
use App\Http\Controllers\Lms\LearnController;
use App\Http\Controllers\Lms\ParentLmsController;
use App\Http\Controllers\Lms\QuestionBankController;
use App\Http\Controllers\Lms\QuizController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| E-learning (LMS) routes
|--------------------------------------------------------------------------
| Admin/teacher management (permission-gated in the controllers), student
| learning experience (enrolment-gated) and read-only parent visibility.
*/

Route::middleware('auth')->group(function () {

    // ── Student learning ────────────────────────────────────────────────
    Route::prefix('learn')->name('lms.learn.')->group(function () {
        Route::get('/', [LearnController::class, 'myCourses'])->name('index');
        Route::get('/catalog', [LearnController::class, 'catalog'])->name('catalog');
        Route::post('/{course}/self-enroll', [LearnController::class, 'selfEnroll'])->whereNumber('course')->name('self-enroll');
        Route::get('/{course}', [LearnController::class, 'show'])->whereNumber('course')->name('show');
        Route::get('/{course}/lesson/{lesson}', [LearnController::class, 'lesson'])->whereNumber(['course', 'lesson'])->name('lesson');
        Route::post('/{course}/lesson/{lesson}/complete', [LearnController::class, 'completeLesson'])->whereNumber(['course', 'lesson'])->name('lesson.complete');
        Route::post('/{course}/assignment/{assignment}/submit', [LearnController::class, 'submitAssignment'])->whereNumber(['course', 'assignment'])->name('assignment.submit');
        Route::get('/{course}/quiz/{quiz}', [LearnController::class, 'takeQuiz'])->whereNumber(['course', 'quiz'])->name('quiz');
        Route::post('/{course}/quiz/{quiz}/submit', [LearnController::class, 'submitQuiz'])->whereNumber(['course', 'quiz'])->name('quiz.submit');
        Route::get('/{course}/quiz/{quiz}/result/{attempt}', [LearnController::class, 'quizResult'])->whereNumber(['course', 'quiz', 'attempt'])->name('quiz.result');
        Route::post('/{course}/discussion', [LearnController::class, 'postDiscussion'])->whereNumber('course')->name('discussion.store');
        Route::delete('/{course}/discussion/{discussion}', [LearnController::class, 'destroyDiscussion'])->whereNumber(['course', 'discussion'])->name('discussion.destroy');
    });

    // ── Parent visibility ───────────────────────────────────────────────
    Route::prefix('learning/children')->name('lms.parent.')->group(function () {
        Route::get('/', [ParentLmsController::class, 'index'])->name('index');
        Route::get('/{student}', [ParentLmsController::class, 'child'])->whereNumber('student')->name('child');
    });

    // ── Question bank (shared, reusable across quizzes) ──────────────────
    Route::prefix('question-bank')->name('lms.bank.')->group(function () {
        Route::get('/', [QuestionBankController::class, 'index'])->name('index');
        Route::get('/candidates', [QuestionBankController::class, 'candidates'])->name('candidates');
        Route::post('/', [QuestionBankController::class, 'store'])->name('store');
        Route::put('/{question}', [QuestionBankController::class, 'update'])->whereNumber('question')->name('update');
        Route::delete('/{question}', [QuestionBankController::class, 'destroy'])->whereNumber('question')->name('destroy');
    });

    // ── Admin / teacher management ──────────────────────────────────────
    Route::prefix('courses')->name('lms.courses.')->group(function () {
        Route::get('/', [CourseController::class, 'index'])->name('index');
        Route::get('/create', [CourseController::class, 'create'])->name('create');
        Route::post('/', [CourseController::class, 'store'])->name('store');
        Route::get('/{course}', [CourseController::class, 'show'])->whereNumber('course')->name('show');
        Route::get('/{course}/edit', [CourseController::class, 'edit'])->whereNumber('course')->name('edit');
        Route::put('/{course}', [CourseController::class, 'update'])->whereNumber('course')->name('update');
        Route::delete('/{course}', [CourseController::class, 'destroy'])->whereNumber('course')->name('destroy');
        Route::post('/{course}/publish', [CourseController::class, 'togglePublish'])->whereNumber('course')->name('publish');
        Route::post('/{course}/duplicate', [CourseController::class, 'duplicate'])->whereNumber('course')->name('duplicate');
        Route::post('/{course}/sync-calendar', [CourseController::class, 'syncCalendar'])->whereNumber('course')->name('sync-calendar');
        Route::post('/bulk/publish', [CourseController::class, 'bulkPublish'])->name('bulk-publish');
    });

    Route::prefix('courses/{course}')->whereNumber('course')->group(function () {
        // Sections
        Route::post('/sections', [ContentController::class, 'storeSection'])->name('lms.sections.store');
        Route::post('/sections/reorder', [ContentController::class, 'reorderSections'])->name('lms.sections.reorder');
        Route::put('/sections/{section}', [ContentController::class, 'updateSection'])->whereNumber('section')->name('lms.sections.update');
        Route::delete('/sections/{section}', [ContentController::class, 'destroySection'])->whereNumber('section')->name('lms.sections.destroy');

        // Lessons
        Route::post('/lessons', [ContentController::class, 'storeLesson'])->name('lms.lessons.store');
        Route::put('/lessons/{lesson}', [ContentController::class, 'updateLesson'])->whereNumber('lesson')->name('lms.lessons.update');
        Route::delete('/lessons/{lesson}', [ContentController::class, 'destroyLesson'])->whereNumber('lesson')->name('lms.lessons.destroy');
        Route::post('/lessons/reorder', [ContentController::class, 'reorderLessons'])->name('lms.lessons.reorder');

        // Enrolment
        Route::get('/learners', [EnrollmentController::class, 'index'])->name('lms.enrollments.index');
        Route::post('/learners/sync', [EnrollmentController::class, 'syncAuto'])->name('lms.enrollments.sync');
        Route::post('/learners/sync-subject', [EnrollmentController::class, 'syncSubject'])->name('lms.enrollments.sync-subject');
        Route::get('/learners/candidates', [EnrollmentController::class, 'candidates'])->name('lms.enrollments.candidates');
        Route::post('/learners', [EnrollmentController::class, 'store'])->name('lms.enrollments.store');
        Route::delete('/learners/{student}', [EnrollmentController::class, 'destroy'])->whereNumber('student')->name('lms.enrollments.destroy');

        // Gradebook
        Route::get('/gradebook', [GradebookController::class, 'show'])->name('lms.gradebook.show');
        Route::get('/gradebook/export', [GradebookController::class, 'export'])->name('lms.gradebook.export');
        Route::get('/gradebook/export-ca', [GradebookController::class, 'exportCa'])->name('lms.gradebook.export-ca');

        // Assignments
        Route::post('/assignments', [AssignmentController::class, 'store'])->name('lms.assignments.store');
        Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->whereNumber('assignment')->name('lms.assignments.update');
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->whereNumber('assignment')->name('lms.assignments.destroy');
        Route::get('/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions'])->whereNumber('assignment')->name('lms.assignments.submissions');
        Route::post('/assignments/{assignment}/submissions/{submission}/grade', [AssignmentController::class, 'grade'])->whereNumber(['assignment', 'submission'])->name('lms.assignments.grade');

        // Quizzes
        Route::post('/quizzes', [QuizController::class, 'store'])->name('lms.quizzes.store');
        Route::get('/quizzes/{quiz}/edit', [QuizController::class, 'edit'])->whereNumber('quiz')->name('lms.quizzes.edit');
        Route::put('/quizzes/{quiz}', [QuizController::class, 'update'])->whereNumber('quiz')->name('lms.quizzes.update');
        Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->whereNumber('quiz')->name('lms.quizzes.destroy');
        Route::get('/quizzes/{quiz}/results', [QuizController::class, 'results'])->whereNumber('quiz')->name('lms.quizzes.results');
        Route::get('/quizzes/{quiz}/review', [QuizController::class, 'review'])->whereNumber('quiz')->name('lms.quizzes.review');
        Route::get('/quizzes/{quiz}/analysis', [QuizController::class, 'itemAnalysis'])->whereNumber('quiz')->name('lms.quizzes.analysis');
        Route::post('/quizzes/{quiz}/attempts/{attempt}/grade', [QuizController::class, 'gradeAttempt'])->whereNumber(['quiz', 'attempt'])->name('lms.attempts.grade');
        Route::post('/quizzes/{quiz}/import-bank', [QuizController::class, 'importBank'])->whereNumber('quiz')->name('lms.quizzes.import-bank');
        Route::post('/quizzes/{quiz}/questions/{question}/to-bank', [QuizController::class, 'saveToBank'])->whereNumber(['quiz', 'question'])->name('lms.questions.to-bank');
        Route::post('/quizzes/{quiz}/questions', [QuizController::class, 'storeQuestion'])->whereNumber('quiz')->name('lms.questions.store');
        Route::put('/quizzes/{quiz}/questions/{question}', [QuizController::class, 'updateQuestion'])->whereNumber(['quiz', 'question'])->name('lms.questions.update');
        Route::delete('/quizzes/{quiz}/questions/{question}', [QuizController::class, 'destroyQuestion'])->whereNumber(['quiz', 'question'])->name('lms.questions.destroy');

        // Live classes
        Route::post('/live', [EngagementController::class, 'storeLive'])->name('lms.live.store');
        Route::put('/live/{live}', [EngagementController::class, 'updateLive'])->whereNumber('live')->name('lms.live.update');
        Route::delete('/live/{live}', [EngagementController::class, 'destroyLive'])->whereNumber('live')->name('lms.live.destroy');

        // Announcements
        Route::post('/announcements', [EngagementController::class, 'storeAnnouncement'])->name('lms.announcements.store');
        Route::delete('/announcements/{announcement}', [EngagementController::class, 'destroyAnnouncement'])->whereNumber('announcement')->name('lms.announcements.destroy');

        // Discussion moderation
        Route::post('/discussions/{discussion}/pin', [EngagementController::class, 'pinDiscussion'])->whereNumber('discussion')->name('lms.discussions.pin');
        Route::post('/discussions/{discussion}/resolve', [EngagementController::class, 'resolveDiscussion'])->whereNumber('discussion')->name('lms.discussions.resolve');
        Route::delete('/discussions/{discussion}', [EngagementController::class, 'destroyDiscussion'])->whereNumber('discussion')->name('lms.discussions.destroy');
    });
});
