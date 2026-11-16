<?php

use App\Modules\Taxonomy\Enums\PublishState;

/*
 * The sitemap lists only pages that may be indexed: nothing while the gate is off (Phase 1), then
 * the main pages and the occupation pages that passed the gate, in both languages (PLAN M14).
 */

beforeEach(function () {
    wave1Setup();
    $this->indexed = listedOccupation();
    $this->indexed->update(['indexable' => true]);
    $this->notIndexed = occupationNamed(['en' => [['name' => 'Pipe fitter', 'type' => 'primary']]], ['publish_state' => PublishState::Listed]);
});

it('lists nothing while the gate is off', function () {
    $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $xml = simplexml_load_string($response->getContent());

    expect($xml->getName())->toBe('urlset')
        ->and($xml->count())->toBe(0);
});

it('lists the main pages and the indexable occupations in both languages once the gate is on', function () {
    config(['atlas.index_gate.enabled' => true]);

    $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
    $locs = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));

    expect($locs)->toContain(url('/'), url('/en'), url('/occupations'), url('/en/occupations'))
        ->toContain(url("/occupations/{$this->indexed->slug}"), url("/en/occupations/{$this->indexed->slug}"))
        ->not->toContain(url("/occupations/{$this->notIndexed->slug}"));

    $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xml->registerXPathNamespace('x', 'http://www.w3.org/1999/xhtml');
    $alternates = $xml->xpath('//s:url[s:loc="'.url("/occupations/{$this->indexed->slug}").'"]/x:link');

    expect(array_map(fn ($link) => [(string) $link['hreflang'], (string) $link['href']], $alternates))->toBe([
        ['bn', url("/occupations/{$this->indexed->slug}")],
        ['en', url("/en/occupations/{$this->indexed->slug}")],
    ]);
});

it('serves a robots.txt that allows public pages and names the sitemap', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('User-agent: *')
        ->assertSee('Disallow: /admin')
        ->assertSee('Disallow: /horizon')
        ->assertDontSee("Disallow: /\n", false)
        ->assertSee('Sitemap: '.url('sitemap.xml'));
});
