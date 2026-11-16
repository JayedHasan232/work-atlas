<?php

use App\Http\Middleware\SecurityHeaders;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Config;

/*
 * PLAN M23 security review (D81): every response carries the security headers, pages, the API, errors
 * and redirects alike.
 */

it('sends the security headers on every kind of response', function (string $path) {
    $response = $this->get($path);

    foreach (SecurityHeaders::HEADERS as $name => $value) {
        $response->assertHeader($name, $value);
    }
})->with([
    'a page' => '/en',
    'the API' => '/api/v1/occupations',
    'a missing page' => '/en/occupations/does-not-exist',
    'a redirect' => '/en/tasks',
    'the health check' => '/up',
]);

it('makes the session cookie Secure when the app is served over HTTPS', function (string $url, ?bool $configured, ?bool $expected) {
    Config::set('app.url', $url);
    Config::set('session.secure', $configured);

    app()->getProvider(AppServiceProvider::class)?->boot();

    expect(config('session.secure'))->toBe($expected);
})->with([
    'HTTPS, not configured' => ['https://example.org', null, true],
    'HTTP, not configured' => ['http://localhost', null, null],
    'HTTPS, turned off on purpose' => ['https://example.org', false, false],
]);
