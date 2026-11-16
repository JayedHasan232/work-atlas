<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;

/*
 * PLAN M23 security review (D81): every route that changes something needs a signed-in person, a
 * rate limit, or both. A new write route without either fails here until it is protected or added
 * to the reviewed list below with a reason.
 */

/** Write routes reviewed and allowed without auth or throttle middleware, with the reason. */
const REVIEWED_OPEN_WRITE_ROUTES = [
    'locale' => 'only stores the chosen language in the session and a cookie',
    'storage/{path}' => 'local-disk uploads: the URL is signed and checked by Laravel',
    'livewire-*/update' => 'Filament components check access on every request; /admin needs a maintainer',
    'reset-password' => 'needs the secret token from the reset mail',
];

it('protects every write route with sign-in or a rate limit', function () {
    $unprotected = collect(Router::getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']) !== [])
        ->reject(fn (Route $route): bool => collect(REVIEWED_OPEN_WRITE_ROUTES)->keys()
            ->contains(fn (string $pattern): bool => Str::is($pattern, $route->uri()) || Str::is("en/{$pattern}", $route->uri())))
        ->reject(fn (Route $route): bool => collect($route->gatherMiddleware())->contains(
            fn (mixed $middleware): bool => is_string($middleware) && preg_match('/^(auth|throttle|horizon|panel:)|Authenticate$/', $middleware) === 1,
        ))
        ->map(fn (Route $route): string => implode('|', $route->methods()).' '.$route->uri())
        ->values()
        ->all();

    expect($unprotected)->toBe([]);
});
