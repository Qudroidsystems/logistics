<?php

use App\Http\Controllers\Exam\ExamBankController;
use App\Http\Controllers\Exam\ExamCoverageController;
use App\Http\Controllers\Exam\ExamPaperController;
use App\Http\Controllers\Exam\ExamScoreController;
use App\Http\Controllers\Exam\ExamVetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Exams (Phase 4): paper building, topic tagging, vetting, per-question
| scores, question bank, and coverage/alignment.
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('exams')->name('exam.')->group(function () {

    // Question bank — declared before papers/{paper} so /bank isn't swallowed.
    Route::prefix('bank')->name('bank.')->group(function () {
        Route::get('/', [ExamBankController::class, 'index'])->name('index');
        Route::get('/fetch', [ExamBankController::class, 'fetch'])->name('fetch');
        Route::post('/', [ExamBankController::class, 'store'])->name('store');
        Route::put('/{question}', [ExamBankController::class, 'update'])->whereNumber('question')->name('update');
        Route::delete('/{question}', [ExamBankController::class, 'destroy'])->whereNumber('question')->name('destroy');
    });

    // Vetting (HOD / Exam Officer)
    Route::prefix('vet')->name('vet.')->group(function () {
        Route::get('/', [ExamVetController::class, 'queue'])->name('queue');
        Route::get('/{paper}', [ExamVetController::class, 'review'])->whereNumber('paper')->name('review');
        Route::post('/{paper}/comment', [ExamVetController::class, 'comment'])->whereNumber('paper')->name('comment');
        Route::post('/{paper}/approve', [ExamVetController::class, 'approve'])->whereNumber('paper')->name('approve');
        Route::post('/{paper}/changes', [ExamVetController::class, 'requestChanges'])->whereNumber('paper')->name('changes');
        Route::post('/{paper}/lock', [ExamVetController::class, 'lock'])->whereNumber('paper')->name('lock');
        Route::post('/{paper}/unlock', [ExamVetController::class, 'unlock'])->whereNumber('paper')->name('unlock');
    });

    // Coverage & alignment
    Route::prefix('coverage')->name('coverage.')->group(function () {
        Route::get('/', [ExamCoverageController::class, 'index'])->name('index');
        Route::get('/{paper}', [ExamCoverageController::class, 'report'])->whereNumber('paper')->name('report');
    });

    // Per-question score grid
    Route::get('/papers/{paper}/scores', [ExamScoreController::class, 'grid'])->whereNumber('paper')->name('scores.grid');
    Route::post('/papers/{paper}/scores', [ExamScoreController::class, 'save'])->whereNumber('paper')->name('scores.save');

    // Papers (teacher)
    Route::prefix('papers')->name('papers.')->group(function () {
        Route::get('/', [ExamPaperController::class, 'index'])->name('index');
        Route::get('/create', [ExamPaperController::class, 'create'])->name('create');
        Route::post('/', [ExamPaperController::class, 'store'])->name('store');
        Route::get('/{paper}', [ExamPaperController::class, 'show'])->whereNumber('paper')->name('show');
        Route::get('/{paper}/edit', [ExamPaperController::class, 'edit'])->whereNumber('paper')->name('edit');
        Route::put('/{paper}', [ExamPaperController::class, 'update'])->whereNumber('paper')->name('update');
        Route::post('/{paper}/submit', [ExamPaperController::class, 'submit'])->whereNumber('paper')->name('submit');
        Route::get('/{paper}/print', [ExamPaperController::class, 'print'])->whereNumber('paper')->name('print');
        Route::get('/{paper}/word', [ExamPaperController::class, 'word'])->whereNumber('paper')->name('word');
        Route::delete('/{paper}', [ExamPaperController::class, 'destroy'])->whereNumber('paper')->name('destroy');
    });
});
