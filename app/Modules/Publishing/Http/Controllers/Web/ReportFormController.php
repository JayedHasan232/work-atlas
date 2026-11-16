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
 * `/occupations/{slug}/report` (PLAN M16): the error report form, open to everyone, optionally about
 * one field (`?field=`). Never indexed.
 */
final class ReportFormController extends Controller
{
    public function __invoke(
        string $slug,
        Request $request,
        OccupationBySlug $slugs,
        PublicOccupations $occupations,
        ContributionFormPresenter $presenter,
        PageRobots $robots,
    ): Response {
        $address = $slugs->handle($slug);
        $occupation = $address === null || $address->redirects() ? null : $occupations->find($address->occupationId);

        if ($occupation === null) {
            abort(404);
        }

        $robots->decide($request, false);
        $field = $request->query('field');

        return Inertia::render('occupations/report', [
            'form' => $presenter->report($occupation, App::getLocale(), is_string($field) ? $field : null),
        ]);
    }
}
