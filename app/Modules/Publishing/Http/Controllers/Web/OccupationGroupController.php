<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\Presenters\BrowsePresenter;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/occupations/groups/{code}`: one ISCO-08 group, with its child groups or, for a unit group, its
 * public occupations (PLAN M13).
 */
final class OccupationGroupController extends Controller
{
    public function __invoke(string $code, BrowsePresenter $browse): Response
    {
        return Inertia::render('occupations/group', $browse->group($code, App::getLocale()) ?? abort(404));
    }
}
