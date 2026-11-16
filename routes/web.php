<?php

use App\Http\Controllers\ContributionTermsController;
use App\Http\Controllers\LocaleController;
use App\Modules\Publishing\Http\Controllers\Web\OccupationGroupController;
use App\Modules\Publishing\Http\Controllers\Web\OccupationIndexController;
use App\Modules\Publishing\Http\Controllers\Web\OccupationPageController;
use App\Modules\Publishing\Http\Middleware\NoIndex;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Route;

// Public pages: Bangla at the root, English under /en (decision A10).
LocalizedRoutes::register(function () {
    Route::inertia('/', 'welcome')->name('home');
    Route::get('contribution-terms', ContributionTermsController::class)->name('contribution-terms');

    // Occupation pages and browsing (PLAN M13), noindex during the Phase 1 beta (rule 12).
    Route::middleware(NoIndex::class)->group(function () {
        Route::get('occupations', OccupationIndexController::class)->name('occupations.index');
        Route::get('occupations/groups/{code}', OccupationGroupController::class)
            ->where('code', '[0-9]{1,4}')
            ->name('occupations.groups.show');
        Route::get('occupations/{slug}', OccupationPageController::class)
            ->where('slug', '[a-z0-9]+(-[a-z0-9]+)*')
            ->name('occupations.show');
    });
});

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
