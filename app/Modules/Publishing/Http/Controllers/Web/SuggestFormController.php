<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\PageRobots;
use App\Modules\Publishing\Presenters\ContributionFormPresenter;
use App\Modules\Taxonomy\Queries\OccupationBySlug;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/occupations/{slug}/suggest/{field}` (PLAN M16): the form to suggest a change to one field, for a
 * signed-in person with a verified email. Never indexed.
 */
final class SuggestFormController extends Controller
{
    public function __invoke(
        string $slug,
        string $field,
        Request $request,
        OccupationBySlug $slugs,
        PublicOccupations $occupations,
        ContributionFormPresenter $presenter,
        PageRobots $robots,
    ): Response {
        $address = $slugs->handle($slug);
        $occupation = $address === null || $address->redirects() ? null : $occupations->find($address->occupationId);
        $form = $occupation === null ? null : $presenter->suggest($occupation, $field, App::getLocale());

        if ($form === null) {
            abort(404);
        }

        $robots->decide($request, false);

        return Inertia::render('occupations/suggest', ['form' => $form]);
    }
}
