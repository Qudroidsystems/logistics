<?php

use App\Http\Controllers\Curriculum\ClassRepController;
use App\Http\Controllers\Curriculum\LessonNoteController;
use App\Http\Controllers\Curriculum\LessonNoteReviewController;
use App\Http\Controllers\Curriculum\TeachingMethodController;
use App\Http\Controllers\Curriculum\TopicConfirmController;
use App\Http\Controllers\Curriculum\TopicController;
use App\Http\Controllers\Curriculum\TopicProgressController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Curriculum: topics, scheme of work, coverage, teacher board, class reps,
| lesson notes & teaching methods.
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('curriculum')->name('curriculum.')->group(function () {

    // Topics & coverage (admin / HOD)
    Route::prefix('topics')->name('topics.')->group(function () {
        Route::get('/', [TopicController::class, 'index'])->name('index');
        Route::get('/coverage', [TopicController::class, 'coverage'])->name('coverage');
        Route::post('/', [TopicController::class, 'store'])->name('store');
        Route::post('/bulk', [TopicController::class, 'bulkAdd'])->name('bulk');
        Route::post('/reorder', [TopicController::class, 'reorder'])->name('reorder');
        Route::put('/{topic}', [TopicController::class, 'update'])->whereNumber('topic')->name('update');
        Route::delete('/{topic}', [TopicController::class, 'destroy'])->whereNumber('topic')->name('destroy');
    });

    // Teacher progress board
    Route::prefix('my-topics')->name('progress.')->group(function () {
        Route::get('/', [TopicProgressController::class, 'index'])->name('index');
        Route::get('/{subjectclass}', [TopicProgressController::class, 'board'])->whereNumber('subjectclass')->name('board');
        Route::post('/{subjectclass}/topic/{topic}/mark', [TopicProgressController::class, 'mark'])->whereNumber(['subjectclass', 'topic'])->name('mark');
        Route::post('/{subjectclass}/topic/{topic}/verify', [TopicProgressController::class, 'verify'])->whereNumber(['subjectclass', 'topic'])->name('verify');
    });

    // Class reps (admin assign + rep confirm)
    Route::prefix('class-reps')->name('reps.')->group(function () {
        Route::get('/confirm', [TopicConfirmController::class, 'index'])->name('confirm');
        Route::post('/confirm/{progress}', [TopicConfirmController::class, 'act'])->whereNumber('progress')->name('act');
        Route::get('/', [ClassRepController::class, 'index'])->name('index');
        Route::post('/', [ClassRepController::class, 'store'])->name('store');
        Route::delete('/{rep}', [ClassRepController::class, 'destroy'])->whereNumber('rep')->name('destroy');
    });

    // Lesson notes
    Route::prefix('lesson-notes')->name('notes.')->group(function () {
        Route::get('/', [LessonNoteController::class, 'index'])->name('index');
        Route::get('/review', [LessonNoteReviewController::class, 'queue'])->name('review');
        Route::get('/create', [LessonNoteController::class, 'create'])->name('create');
        Route::post('/', [LessonNoteController::class, 'store'])->name('store');
        Route::get('/{note}', [LessonNoteController::class, 'show'])->whereNumber('note')->name('show');
        Route::get('/{note}/edit', [LessonNoteController::class, 'edit'])->whereNumber('note')->name('edit');
        Route::put('/{note}', [LessonNoteController::class, 'update'])->whereNumber('note')->name('update');
        Route::post('/{note}/submit', [LessonNoteController::class, 'submit'])->whereNumber('note')->name('submit');
        Route::post('/{note}/deliver', [LessonNoteController::class, 'deliver'])->whereNumber('note')->name('deliver');
        Route::post('/{note}/copy', [LessonNoteController::class, 'copy'])->whereNumber('note')->name('copy');
        Route::delete('/{note}', [LessonNoteController::class, 'destroy'])->whereNumber('note')->name('destroy');
        Route::post('/{note}/approve', [LessonNoteReviewController::class, 'approve'])->whereNumber('note')->name('approve');
        Route::post('/{note}/return', [LessonNoteReviewController::class, 'return'])->whereNumber('note')->name('return');
    });

    // Teaching methods library (admin)
    Route::prefix('teaching-methods')->name('methods.')->group(function () {
        Route::get('/', [TeachingMethodController::class, 'index'])->name('index');
        Route::post('/', [TeachingMethodController::class, 'store'])->name('store');
        Route::put('/{method}', [TeachingMethodController::class, 'update'])->whereNumber('method')->name('update');
        Route::delete('/{method}', [TeachingMethodController::class, 'destroy'])->whereNumber('method')->name('destroy');
    });
});
