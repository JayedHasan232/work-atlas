<?php

use Inertia\Testing\AssertableInertia as Assert;

/*
 * The search page (PLAN M15): results in the reader's language with the name that matched, never
 * indexed, in both languages.
 */

beforeEach(function () {
    wave1Setup();
    $this->plumber = listedOccupation();
});

it('shows the occupation and the local name that matched', function () {
    $this->get('/search?q='.urlencode('নলমিস্ত্রি'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('search/index')
            ->where('query', 'নলমিস্ত্রি')
            ->where('results.0.slug', $this->plumber->slug)
            ->where('results.0.name', 'প্লাম্বার')
            ->where('results.0.matchedName', 'নলমিস্ত্রি')
            ->where('results.0.match', 'exact')
            ->where('results.0.url', "/occupations/{$this->plumber->slug}"));
});

it('links to the English page and shows the English name on the English search page', function () {
    $this->get('/en/search?q=plumber')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('results.0.name', 'Plumber')
            ->where('results.0.matchedName', null)
            ->where('results.0.url', "/en/occupations/{$this->plumber->slug}"));
});

it('shows no results and no query for an empty search', function () {
    $this->get('/search')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('query', '')->where('results', []));
});

it('cuts a very long query', function () {
    $this->get('/search?q='.str_repeat('a', 500))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('query', str_repeat('a', 100)));
});

it('is never indexed, even with the index gate on', function () {
    config(['atlas.index_gate.enabled' => true]);

    $this->get('/en/search?q=plumber')->assertHeader('X-Robots-Tag', 'noindex');
    $this->get('/robots.txt')->assertSee('Disallow: /search')->assertSee('Disallow: /en/search');
});
