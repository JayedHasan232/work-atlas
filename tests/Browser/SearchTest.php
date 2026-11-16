<?php

// PLAN M15: the header search box finds an occupation by a local name and leads to its page, in a
// real browser, without JavaScript errors or accessibility issues.

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
});

test('the header search finds an occupation by its local name', function () {
    $page = visit('/occupations');

    $page->fill('q', 'নলমিস্ত্রি')
        ->press('খুঁজুন')
        ->assertPathIs('/search')
        ->assertSee('১টি পেশা পাওয়া গেছে')
        ->assertSee('প্লাম্বার')
        ->assertSee('অন্য নাম নলমিস্ত্রি')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues()
        ->click('প্লাম্বার')
        ->assertPathIs("/occupations/{$this->occupation->slug}");
});

test('the English search page says when nothing matches', function () {
    $page = visit('/en/search?q=zzzz');

    $page->assertSee('No occupation matches “zzzz”.')
        ->assertSee('Browse all occupations')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});
