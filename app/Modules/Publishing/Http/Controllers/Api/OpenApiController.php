<?php

namespace App\Modules\Publishing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\Http\ApiResponse;
use App\Modules\Publishing\OpenApiSpec;
use Illuminate\Http\JsonResponse;

/**
 * `GET /api/v1/openapi.json`: the OpenAPI 3.1 description of this API.
 */
final class OpenApiController extends Controller
{
    public function __invoke(OpenApiSpec $spec): JsonResponse
    {
        return ApiResponse::json($spec->document());
    }
}
