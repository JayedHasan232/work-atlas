<?php

namespace App\Modules\Discovery\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Discovery\Data\GuideAnswers;
use App\Modules\Publishing\PageRobots;
use App\Modules\Publishing\Presenters\GuidePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/guide` (PLAN M20): the guided filter. The answers come in the URL and are read for this response
 * only; nothing about them is stored (D73).
 */
final class GuideController extends Controller
{
    public function __invoke(Request $request, GuidePresenter $presenter, PageRobots $robots): Response
    {
        $robots->decide($request, false);

        return Inertia::render('discovery/guide', [
            'guide' => $presenter->present(GuideAnswers::from($request->query()), App::getLocale()),
        ]);
    }
}
