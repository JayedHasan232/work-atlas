<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\Presenters\BrowsePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/occupations`: the ISCO-08 major groups and every public occupation, by name (PLAN M13).
 */
final class OccupationIndexController extends Controller
{
    public function __invoke(Request $request, BrowsePresenter $browse): Response
    {
        return Inertia::render('occupations/index', $browse->index(App::getLocale(), $request->integer('page', 1)));
    }
}
