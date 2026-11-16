<?php

namespace App\Modules\Publishing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\Http\ApiResponse;
use App\Modules\Publishing\Presenters\ApiPresenter;
use App\Support\Localization\Locales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * `GET /api/v1/search`: occupations by any of their names, local and regional ones included.
 */
final class SearchController extends Controller
{
    public function __invoke(Request $request, ApiPresenter $api): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'locale' => ['nullable', Rule::in(Locales::codes())],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return ApiResponse::json($api->search(
            trim($request->string('q')->toString()),
            $request->string('locale', Locales::default())->toString(),
            $request->integer('limit', 20),
        ));
    }
}
