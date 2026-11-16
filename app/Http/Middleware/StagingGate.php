<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staging is private (PLAN M22, A12): HTTP basic auth on every request except the health check,
 * and `noindex` on every response. Fails closed: with no user or password configured, nobody gets
 * in. Outside APP_ENV=staging it does nothing.
 */
final class StagingGate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! App::environment('staging')) {
            return $next($request);
        }

        $response = $request->is('up') || $this->authorised($request)
            ? $next($request)
            : new Response(status: 401, headers: ['WWW-Authenticate' => 'Basic realm="Work Atlas staging", charset="UTF-8"']);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    private function authorised(Request $request): bool
    {
        $user = Config::get('atlas.staging.basic_auth_user');
        $password = Config::get('atlas.staging.basic_auth_password');

        if (! is_string($user) || $user === '' || ! is_string($password) || $password === '') {
            return false;
        }

        return hash_equals($user, $request->getUser() ?? '')
            && hash_equals($password, $request->getPassword() ?? '');
    }
}
