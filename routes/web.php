<?php

use App\Http\Controllers\ContributionTermsController;
use App\Http\Controllers\LocaleController;
use App\Modules\Community\Http\Controllers\Web\DiscussionController;
use App\Modules\Community\Http\Controllers\Web\EditSuggestionController;
use App\Modules\Community\Http\Controllers\Web\ErrorReportController;
use App\Modules\Community\Http\Controllers\Web\Moderation\DiscussionModerationController;
use App\Modules\Community\Http\Controllers\Web\Moderation\ModerationActionController;
use App\Modules\Community\Http\Controllers\Web\Moderation\PatrolEditController;
use App\Modules\Community\Http\Controllers\Web\Moderation\PatrolQueueController;
use App\Modules\Community\Http\Controllers\Web\Moderation\ReviewQueueController;
use App\Modules\Community\Http\Controllers\Web\Moderation\ReviewSlotController;
use App\Modules\Community\Http\Controllers\Web\Moderation\TalkQueueController;
use App\Modules\Community\Http\Controllers\Web\TaskAnswerController;
use App\Modules\Discovery\Http\Controllers\Web\CompareController;
use App\Modules\Discovery\Http\Controllers\Web\GuideController;
use App\Modules\Publishing\Enums\Policy;
use App\Modules\Publishing\Http\Controllers\Web\ApiDocsController;
use App\Modules\Publishing\Http\Controllers\Web\ContributorController;
use App\Modules\Publishing\Http\Controllers\Web\MicroTaskController;
use App\Modules\Publishing\Http\Controllers\Web\OccupationGroupController;
use App\Modules\Publishing\Http\Controllers\Web\OccupationIndexController;
use App\Modules\Publishing\Http\Controllers\Web\OccupationPageController;
use App\Modules\Publishing\Http\Controllers\Web\PolicyController;
use App\Modules\Publishing\Http\Controllers\Web\ReportFormController;
use App\Modules\Publishing\Http\Controllers\Web\RobotsTxtController;
use App\Modules\Publishing\Http\Controllers\Web\SearchController;
use App\Modules\Publishing\Http\Controllers\Web\SitemapController;
use App\Modules\Publishing\Http\Controllers\Web\SuggestFormController;
use App\Modules\Publishing\Http\Controllers\Web\TalkIndexController;
use App\Modules\Publishing\Http\Controllers\Web\TalkThreadController;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Route;

// Record ids in a URL are `[0-9]{1,18}`: a longer number would not fit PHP's int and end in an error page.

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

    // Contributions (PLAN M16): anyone reports an error; a signed-in person suggests a change.
    Route::where(['slug' => '[a-z0-9]+(-[a-z0-9]+)*', 'field' => '[a-z0-9_]+'])->group(function () {
        Route::get('occupations/{slug}/report', ReportFormController::class)->name('occupations.report');
        Route::post('occupations/{slug}/report', ErrorReportController::class)
            ->middleware('throttle:error-reports')
            ->name('occupations.report.store');

        Route::middleware(['auth', 'verified'])->group(function () {
            Route::get('occupations/{slug}/suggest/{field}', SuggestFormController::class)->name('occupations.suggest');
            Route::post('occupations/{slug}/suggest/{field}', EditSuggestionController::class)
                ->middleware('throttle:suggestions')
                ->name('occupations.suggest.store');
        });
    });
    // Talk pages (PLAN M19): anyone reads; signed-in people with a verified email write.
    Route::where(['slug' => '[a-z0-9]+(-[a-z0-9]+)*'])->group(function () {
        Route::get('occupations/{slug}/talk', TalkIndexController::class)->name('talk.index');
        Route::get('occupations/{slug}/talk/{thread}', TalkThreadController::class)->where('thread', '[0-9]{1,18}')->name('talk.show');

        Route::middleware(['auth', 'verified', 'throttle:suggestions'])->group(function () {
            Route::post('occupations/{slug}/talk', [DiscussionController::class, 'store'])->name('talk.store');
            Route::post('occupations/{slug}/talk/{thread}', [DiscussionController::class, 'reply'])->where('thread', '[0-9]{1,18}')->name('talk.reply');
        });
    });
    Route::get('contributors/{id}', ContributorController::class)->where('id', '[0-9]{1,18}')->name('contributors.show');

    // Micro-tasks (PLAN M17): one yes/no question at a time, for signed-in people.
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('tasks', MicroTaskController::class)->name('tasks.show');
        Route::post('tasks/{task}', TaskAnswerController::class)
            ->where('task', '[0-9]{1,18}')
            ->middleware('throttle:answers')
            ->name('tasks.answer');
    });

    // Moderation (PLAN M18, D66): review desk and patrol queue for reviewers and above.
    Route::middleware(['auth', 'verified', 'can:moderate'])->group(function () {
        Route::get('review', ReviewQueueController::class)->name('review.index');
        Route::get('review/{slot}', ReviewSlotController::class)->where('slot', '[0-9]{1,18}')->name('review.show');
        Route::post('review/{slot}/proposals/{revision}', [ModerationActionController::class, 'decide'])
            ->where(['slot' => '[0-9]{1,18}', 'revision' => '[0-9]{1,18}'])
            ->name('review.decide');
        Route::post('review/{slot}/status', [ModerationActionController::class, 'status'])->where('slot', '[0-9]{1,18}')->name('review.status');
        Route::get('patrol', PatrolQueueController::class)->name('patrol.index');
        Route::get('patrol/{revision}', PatrolEditController::class)->where('revision', '[0-9]{1,18}')->name('patrol.show');
        Route::post('patrol/{revision}/patrolled', [ModerationActionController::class, 'patrol'])->where('revision', '[0-9]{1,18}')->name('patrol.mark');
        Route::post('patrol/{revision}/revert', [ModerationActionController::class, 'revert'])->where('revision', '[0-9]{1,18}')->name('patrol.revert');
        Route::get('patrol/talk', TalkQueueController::class)->name('patrol.talk');
        Route::post('talk/posts/{post}/hide', [DiscussionModerationController::class, 'hidePost'])->where('post', '[0-9]{1,18}')->name('talk.posts.hide');
        Route::post('talk/posts/{post}/show', [DiscussionModerationController::class, 'showPost'])->where('post', '[0-9]{1,18}')->name('talk.posts.show');
        Route::post('talk/threads/{thread}/hide', [DiscussionModerationController::class, 'hideThread'])->where('thread', '[0-9]{1,18}')->name('talk.threads.hide');
        Route::post('talk/threads/{thread}/show', [DiscussionModerationController::class, 'showThread'])->where('thread', '[0-9]{1,18}')->name('talk.threads.show');
    });

    // Search (PLAN M15).
    Route::get('search', SearchController::class)->name('search');

    // Guided filter and compare (PLAN M20): the answers and the chosen occupations live in the URL.
    Route::get('guide', GuideController::class)->name('guide.index');
    Route::get('compare', CompareController::class)->name('compare.index');

    // The public API's docs (PLAN M21).
    Route::get('developers', ApiDocsController::class)->name('developers');

    // Policy pages (PLAN M23), drafts until the maintainer approves them.
    Route::get('policies', [PolicyController::class, 'index'])->name('policies.index');
    Route::get('policies/{policy}', [PolicyController::class, 'show'])
        ->whereIn('policy', Policy::slugs())
        ->name('policies.show');
});

// For crawlers (PLAN M14): not localized.
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', RobotsTxtController::class)->name('robots');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
