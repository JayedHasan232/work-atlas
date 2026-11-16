<?php

namespace App\Modules\Publishing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\Http\ApiResponse;
use App\Modules\Publishing\Presenters\ApiPresenter;
use Illuminate\Http\JsonResponse;

/**
 * `GET /api/v1/fields`: what each field means — labels, options, scale anchors, and whether it is
 * sensitive or needs a source.
 */
final class FieldIndexController extends Controller
{
    public function __invoke(ApiPresenter $api): JsonResponse
    {
        return ApiResponse::json($api->fields());
    }
}
