<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\PageRobots;
use App\Modules\Publishing\Presenters\SearchPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/search?q=` (PLAN M15). Result pages are never indexed: every query would be a page of its own.
 */
final class SearchController extends Controller
{
    public function __invoke(Request $request, SearchPresenter $presenter, PageRobots $robots): Response
    {
        $robots->decide($request, false);

        return Inertia::render('search/index', $presenter->present(is_string($query = $request->query('q')) ? $query : '', App::getLocale()));
    }
}
