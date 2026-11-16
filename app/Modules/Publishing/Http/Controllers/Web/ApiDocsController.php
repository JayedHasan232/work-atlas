<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\OpenApiSpec;
use App\Modules\Publishing\PageRobots;
use App\Modules\Publishing\Presenters\ApiPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/developers` (PLAN M21): how to use the public API — the endpoints from the OpenAPI description,
 * the license and attribution, and the rate limit.
 */
final class ApiDocsController extends Controller
{
    public function __invoke(Request $request, OpenApiSpec $spec, ApiPresenter $api, PageRobots $robots): Response
    {
        $robots->decide($request, false);
        $meta = $api->meta();

        return Inertia::render('publishing/api-docs', [
            'docs' => [
                'operations' => $spec->operations(),
                'baseUrl' => url('/api/v1'),
                'specUrl' => route('api.v1.openapi'),
                'license' => $meta['license'],
                'licenseUrl' => $meta['license_url'],
                'attribution' => $meta['attribution'],
                'perMinute' => Config::integer('atlas.api.per_minute'),
            ],
        ]);
    }
}
