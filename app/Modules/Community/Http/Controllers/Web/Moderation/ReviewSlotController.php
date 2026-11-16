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
 * One value on the review desk: the current value, its waiting proposals and the status choices (PLAN M18, D66). Reviewers and above; never indexed.
 */
final class ReviewSlotController extends Controller
{
    public function __invoke(Request $request, int $slot, ModerationPresenter $presenter, PageRobots $robots): Response
    {
        $robots->decide($request, false);
        $props = $presenter->reviewSlot($slot, $request->user() ?? abort(403), App::getLocale()) ?? abort(404);

        return Inertia::render('moderation/review-slot', ['slot' => $props]);
    }
}
