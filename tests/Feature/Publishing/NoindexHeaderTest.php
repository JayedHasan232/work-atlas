<?php

use App\Models\User;

/*
 * Rule 12: during the Phase 1 beta every page is noindex, in the HTML and in the X-Robots-Tag
 * header (PLAN M14). Once the gate is on, public pages may be indexed; private ones never are.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
});

it('marks every page noindex while the gate is off', function (string $url) {
    $url = str_replace('{slug}', $this->occupation->slug, $url);
    $response = $this->get($url);

    $response->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee('<meta name="robots" content="noindex">', false);
})->with([
    '/', '/en', '/contribution-terms', '/en/contribution-terms', '/occupations', '/en/occupations',
    '/occupations/groups/7', '/occupations/{slug}', '/en/occupations/{slug}', '/login', '/register',
]);

it('marks a signed-in page noindex', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex');
});

it('lets public pages be indexed once the gate is on, but never private ones', function () {
    config(['atlas.index_gate.enabled' => true]);

    foreach (['/', '/en', '/occupations', '/en/occupations/groups/7'] as $url) {
        $this->get($url)->assertOk()->assertHeaderMissing('X-Robots-Tag')
            ->assertDontSee('<meta name="robots"', false);
    }

    foreach (['/login', '/register'] as $url) {
        $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
    }
});

it('keeps an occupation page noindex until it passes the gate', function () {
    config(['atlas.index_gate.enabled' => true]);

    $this->get("/occupations/{$this->occupation->slug}")->assertOk()->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee('<meta name="robots" content="noindex">', false);

    $this->occupation->update(['indexable' => true]);

    $this->get("/occupations/{$this->occupation->slug}")->assertOk()->assertHeaderMissing('X-Robots-Tag');
});

it('never lets the API be indexed, whatever the gate says', function () {
    config(['atlas.index_gate.enabled' => true]);
    $this->occupation->update(['indexable' => true]);

    foreach (['/api/v1/occupations', "/api/v1/occupations/{$this->occupation->slug}", '/api/v1/fields', '/api/v1/search?q=plumber'] as $url) {
        $this->getJson($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
    }
});
