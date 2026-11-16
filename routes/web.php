<?php

use App\Http\Controllers\ContributionTermsController;
use App\Http\Controllers\LocaleController;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Route;

// Public pages: Bangla at the root, English under /en (decision A10).
LocalizedRoutes::register(function () {
    Route::inertia('/', 'welcome')->name('home');
    Route::get('contribution-terms', ContributionTermsController::class)->name('contribution-terms');
});

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
