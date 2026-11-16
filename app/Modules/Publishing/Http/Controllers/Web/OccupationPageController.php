<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\PageRobots;
use App\Modules\Publishing\Presenters\OccupationPresenter;
use App\Modules\Taxonomy\Queries\OccupationBySlug;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/occupations/{slug}` (A10). A listed or published occupation has a page (PLAN M13); a merged
 * occupation's old slug redirects permanently to the occupation it was merged into, in the same
 * language (D5); a candidate or rejected occupation is not found.
 */
final class OccupationPageController extends Controller
{
    public function __invoke(
        string $slug,
        OccupationBySlug $slugs,
        PublicOccupations $occupations,
        OccupationPresenter $presenter,
        PageRobots $robots,
        Request $request,
    ): Response|RedirectResponse {
        $address = $slugs->handle($slug);

        if ($address === null) {
            abort(404);
        }

        if ($address->redirects()) {
            return redirect()->to(route((string) Route::currentRouteName(), ['slug' => $address->canonicalSlug]), 301);
        }

        $occupation = $occupations->find($address->occupationId) ?? abort(404);
        $robots->decide($request, $occupation->indexable);

        return Inertia::render('occupations/show', [
            'occupation' => $presenter->present($occupation, App::getLocale()),
        ]);
    }
}
