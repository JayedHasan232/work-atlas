<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\PageRobots;
use App\Modules\Publishing\Presenters\ContributorPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/contributors/{id}` (PLAN M16): a contributor's public profile. A person who chose anonymous credit
 * has none. Never indexed: it is about a person, not an occupation (D63).
 */
final class ContributorController extends Controller
{
    public function __invoke(int $id, Request $request, ContributorPresenter $presenter, PageRobots $robots): Response
    {
        $contributor = $presenter->present($id, App::getLocale()) ?? abort(404);
        $robots->decide($request, false);

        return Inertia::render('contributors/show', ['contributor' => $contributor]);
    }
}
