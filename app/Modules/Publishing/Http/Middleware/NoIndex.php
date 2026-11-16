<?php

namespace App\Modules\Publishing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps occupation pages out of search engines during the Phase 1 beta (rule 12): an `X-Robots-Tag`
 * header, and a `robots` prop that the page renders as a meta tag. The index gate (M14) decides per
 * page later.
 */
final class NoIndex
{
    public const string DIRECTIVE = 'noindex';

    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share('robots', self::DIRECTIVE);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', self::DIRECTIVE);

        return $response;
    }
}
