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
 * The patrol queue: people's recent edits nobody has looked at, flagged ones first (PLAN M18, D66). Reviewers and above; never indexed.
 */
final class PatrolQueueController extends Controller
{
    public function __invoke(Request $request, ModerationPresenter $presenter, PageRobots $robots): Response
    {
        $robots->decide($request, false);

        return Inertia::render('moderation/patrol-queue', $presenter->patrolQueue(App::getLocale()));
    }
}
