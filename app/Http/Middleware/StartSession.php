<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession as BaseStartSession;

/**
 * Laravel keeps the last page's full URL in the session. The guided filter's answers are in its query
 * string and belong to the person (rule 8, PLAN D73), so for the guide only the bare path is kept.
 */
class StartSession extends BaseStartSession
{
    /** @var list<string> */
    private const array PRIVATE_QUERY_ROUTES = ['guide.index', '*.guide.index'];

    /**
     * @param  Session  $session
     */
    protected function storeCurrentUrl(Request $request, $session): void
    {
        parent::storeCurrentUrl($request, $session);

        if ($request->routeIs(...self::PRIVATE_QUERY_ROUTES) && $session->previousUrl() !== null) {
            $session->setPreviousUrl($request->url());
        }
    }
}
