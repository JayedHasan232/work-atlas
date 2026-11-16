<?php

namespace App\Modules\Community\Http\Controllers\Web\Moderation;

use App\Http\Controllers\Controller;
use App\Modules\Community\Presenters\ModerationPresenter;
use App\Modules\Publishing\PageRobots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The review queue: values waiting for a reviewer, model disagreements and disputes first (PLAN M18, D66). Reviewers and above; never indexed.
 */
final class ReviewQueueController extends Controller
{
    public function __invoke(Request $request, ModerationPresenter $presenter, PageRobots $robots): Response
    {
        $robots->decide($request, false);

        return Inertia::render('moderation/review-queue', $presenter->reviewQueue(App::getLocale()));
    }
}
