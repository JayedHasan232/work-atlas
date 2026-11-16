<?php

namespace App\Modules\Publishing\Http;

use Illuminate\Http\JsonResponse;

/**
 * JSON as the API sends it: Bangla and URLs unescaped, cacheable for a few minutes.
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status, ['Cache-Control' => 'public, max-age=300'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
