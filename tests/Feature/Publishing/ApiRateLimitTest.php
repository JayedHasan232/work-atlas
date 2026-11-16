<?php

/*
 * PLAN M21: the public API limits requests per IP address (config('atlas.api.per_minute')) and may be
 * read from any website, without credentials.
 */

beforeEach(function () {
    wave1Setup();
    config(['atlas.api.per_minute' => 3]);
});

it('limits requests per IP address', function () {
    foreach (range(1, 3) as $request) {
        $this->getJson('/api/v1/fields')->assertOk()->assertHeader('X-RateLimit-Limit', '3');
    }

    $this->getJson('/api/v1/fields')->assertStatus(429)->assertHeader('Retry-After');
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->getJson('/api/v1/fields')->assertOk();
});

it('counts every endpoint together', function () {
    $this->getJson('/api/v1/fields')->assertOk();
    $this->getJson('/api/v1/occupations')->assertOk();
    $this->getJson('/api/v1/search?q=a')->assertOk();

    $this->getJson('/api/v1/openapi.json')->assertStatus(429);
});

it('allows reads from any website, without credentials', function () {
    $this->getJson('/api/v1/fields', ['Origin' => 'https://example.org'])
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', '*')
        ->assertHeaderMissing('Access-Control-Allow-Credentials');
});

it('answers a preflight request for reads only', function () {
    $response = $this->call('OPTIONS', '/api/v1/fields', server: [
        'HTTP_ORIGIN' => 'https://example.org',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ]);

    expect($response->getStatusCode())->toBe(204)
        ->and((string) $response->headers->get('Access-Control-Allow-Methods'))->not->toContain('POST');
});
