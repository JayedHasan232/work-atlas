<?php

namespace App\Modules\Publishing\Http;

use App\Modules\Publishing\PageRobots;
use Illuminate\Http\JsonResponse;

/**
 * JSON as the API sends it: Bangla and URLs unescaped, cacheable for a few minutes. The API is data,
 * not pages: it is never indexed (rule 12), whatever the index gate says.
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status, ['Cache-Control' => 'public, max-age=300', 'X-Robots-Tag' => PageRobots::NOINDEX], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
