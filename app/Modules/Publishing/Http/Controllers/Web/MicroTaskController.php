<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\PageRobots;
use App\Modules\Publishing\Presenters\MicroTaskPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The micro-task page (PLAN M17): one question at a time, for a signed-in person. Never indexed.
 */
final class MicroTaskController extends Controller
{
    public function __invoke(Request $request, MicroTaskPresenter $presenter, PageRobots $robots): Response
    {
        $robots->decide($request, false);

        return Inertia::render('tasks/show', $presenter->present((int) $request->user()?->getAuthIdentifier(), App::getLocale()));
    }
}
