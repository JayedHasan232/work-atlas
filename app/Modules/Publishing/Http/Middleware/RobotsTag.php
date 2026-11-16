<?php

namespace App\Modules\Publishing\Http\Middleware;

use App\Modules\Publishing\PageRobots;
use App\Support\Localization\LocalizedRoutes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every web response says whether it may be indexed (rule 12, PLAN M14). Public pages (the ones
 * registered per language) qualify once the index gate is on, unless their controller decides
 * otherwise, as an occupation page does; every other page (sign-in, settings) never does.
 */
final class RobotsTag
{
    public function __construct(private readonly PageRobots $robots) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->robots->decide($request, LocalizedRoutes::localeOf($request->route()) !== null);

        /** @var Response $response */
        $response = $next($request);

        if (! $this->robots->indexable($request)) {
            $response->headers->set('X-Robots-Tag', PageRobots::NOINDEX);
        }

        return $response;
    }
}
