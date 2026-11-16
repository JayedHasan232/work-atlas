<?php

namespace App\Modules\Discovery\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\PageRobots;
use App\Modules\Publishing\Presenters\ComparePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/compare?o[]=…` (PLAN M20): up to three occupations side by side, chosen in the URL.
 */
final class CompareController extends Controller
{
    public function __invoke(Request $request, ComparePresenter $presenter, PageRobots $robots): Response
    {
        $robots->decide($request, false);
        $chosen = $request->query('o');
        $query = $request->query('q');

        return Inertia::render('discovery/compare', [
            'compare' => $presenter->present(
                array_values(array_filter(is_array($chosen) ? $chosen : [$chosen], 'is_string')),
                is_string($query) && trim($query) !== '' ? mb_substr(trim($query), 0, 100) : null,
                App::getLocale(),
            ),
        ]);
    }
}
