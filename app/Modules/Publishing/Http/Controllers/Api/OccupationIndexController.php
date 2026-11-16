<?php

namespace App\Modules\Publishing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\Http\ApiResponse;
use App\Modules\Publishing\Presenters\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `GET /api/v1/occupations`: the public occupations, a page at a time, optionally in one ISCO group.
 */
final class OccupationIndexController extends Controller
{
    public function __invoke(Request $request, ApiPresenter $api): JsonResponse
    {
        $input = $request->validate([
            'group' => ['nullable', 'string', 'regex:/^[0-9]{1,4}$/'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return ApiResponse::json($api->occupations(
            $request->integer('page', 1),
            $request->integer('per_page', 50),
            isset($input['group']) ? $request->string('group')->toString() : null,
        ));
    }
}
