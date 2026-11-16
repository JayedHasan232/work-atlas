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
 * `/patrol/talk` (PLAN M19): recent talk-page posts for moderators, flagged ones first.
 */
final class TalkQueueController extends Controller
{
    public function __invoke(Request $request, ModerationPresenter $presenter, PageRobots $robots): Response
    {
        $robots->decide($request, false);

        return Inertia::render('moderation/talk-queue', $presenter->talkQueue(App::getLocale()));
    }
}
