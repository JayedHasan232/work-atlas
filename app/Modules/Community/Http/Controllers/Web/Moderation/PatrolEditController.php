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
 * One edit: the diff, sources, comment, declared interest and flags, with revert and mark patrolled (PLAN M18, D66). Reviewers and above; never indexed.
 */
final class PatrolEditController extends Controller
{
    public function __invoke(Request $request, int $revision, ModerationPresenter $presenter, PageRobots $robots): Response
    {
        $robots->decide($request, false);
        $props = $presenter->patrolEdit($revision, $request->user() ?? abort(403), App::getLocale()) ?? abort(404);

        return Inertia::render('moderation/patrol-edit', ['edit' => $props]);
    }
}
