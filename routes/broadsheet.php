<?php

use App\Http\Controllers\Broadsheet\BroadsheetRankingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Broadsheet ranking settings (unofficial best-student ranking).
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('broadsheet-ranking')->name('broadsheet.ranking.')->group(function () {
    Route::get('/', [BroadsheetRankingController::class, 'index'])->name('index');
    Route::post('/{section}', [BroadsheetRankingController::class, 'save'])
        ->whereIn('section', ['junior', 'senior'])->name('save');
});
