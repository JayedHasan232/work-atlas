<?php

use App\Http\Controllers\ContributionTermsController;
use App\Http\Controllers\LocaleController;
use App\Modules\Publishing\Http\Controllers\Web\OccupationGroupController;
use App\Modules\Publishing\Http\Controllers\Web\OccupationIndexController;
use App\Modules\Publishing\Http\Controllers\Web\OccupationPageController;
use App\Modules\Publishing\Http\Controllers\Web\RobotsTxtController;
use App\Modules\Publishing\Http\Controllers\Web\SearchController;
use App\Modules\Publishing\Http\Controllers\Web\SitemapController;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Route;

// Public pages: Bangla at the root, English under /en (decision A10).
LocalizedRoutes::register(function () {
    Route::inertia('/', 'welcome')->name('home');
    Route::get('contribution-terms', ContributionTermsController::class)->name('contribution-terms');

    // Occupation pages and browsing (PLAN M13).
    Route::get('occupations', OccupationIndexController::class)->name('occupations.index');
    Route::get('occupations/groups/{code}', OccupationGroupController::class)
        ->where('code', '[0-9]{1,4}')
        ->name('occupations.groups.show');
    Route::get('occupations/{slug}', OccupationPageController::class)
        ->where('slug', '[a-z0-9]+(-[a-z0-9]+)*')
        ->name('occupations.show');

    // Search (PLAN M15).
    Route::get('search', SearchController::class)->name('search');
});

// For crawlers (PLAN M14): not localized.
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', RobotsTxtController::class)->name('robots');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
