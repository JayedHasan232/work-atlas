<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\PageRobots;
use App\Modules\Publishing\Presenters\TalkPresenter;
use App\Modules\Taxonomy\Queries\OccupationBySlug;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/occupations/{slug}/talk` (PLAN M19): an occupation's discussions and the form for a new one.
 * Talk pages are never indexed. A merged occupation's old slug redirects to the occupation it went into.
 */
final class TalkIndexController extends Controller
{
    public function __invoke(
        string $slug,
        Request $request,
        OccupationBySlug $slugs,
        PublicOccupations $occupations,
        TalkPresenter $presenter,
        PageRobots $robots,
    ): Response|RedirectResponse {
        $address = $slugs->handle($slug) ?? abort(404);

        if ($address->redirects()) {
            return redirect()->to(route((string) Route::currentRouteName(), ['slug' => $address->canonicalSlug]), 301);
        }

        $occupation = $occupations->find($address->occupationId) ?? abort(404);
        $robots->decide($request, false);
        $user = $request->user();
        $field = $request->query('field');

        return Inertia::render('talk/index', ['talk' => $presenter->index(
            $occupation,
            $user,
            App::getLocale(),
            is_string($field) ? $field : null,
        )]);
    }
}
