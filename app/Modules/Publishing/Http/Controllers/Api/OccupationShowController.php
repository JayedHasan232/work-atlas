<?php

namespace App\Modules\Publishing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\Http\ApiResponse;
use App\Modules\Publishing\Presenters\ApiPresenter;
use App\Modules\Taxonomy\Queries\OccupationBySlug;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * `GET /api/v1/occupations/{slug}`: one public occupation with its field values and sources. A merged
 * occupation's old slug redirects to the occupation it was merged into.
 */
final class OccupationShowController extends Controller
{
    public function __invoke(string $slug, OccupationBySlug $slugs, PublicOccupations $occupations, ApiPresenter $api): JsonResponse|RedirectResponse
    {
        $address = $slugs->handle($slug);

        if ($address !== null && $address->redirects()) {
            return redirect()->route('api.v1.occupations.show', ['slug' => $address->canonicalSlug], 301);
        }

        $occupation = $address === null ? null : $occupations->find($address->occupationId);

        if ($occupation === null) {
            abort(404);
        }

        return ApiResponse::json($api->occupation($occupation));
    }
}
