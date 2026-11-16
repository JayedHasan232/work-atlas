<?php

use App\Modules\Knowledge\Models\FieldDefinition;

// PLAN M13: the occupation page and the index work in a real browser, in both languages, without
// JavaScript errors or accessibility issues.

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    recordValue(FieldDefinition::query()->where('key', 'physical_workload')->sole(), 4, occupationId: $this->occupation->id);
});

test('the occupation page shows the names, a value and its status in both languages', function () {
    $page = visit("/occupations/{$this->occupation->slug}");

    $page->assertSee('প্লাম্বার')
        ->assertSee('নলমিস্ত্রি')
        ->assertSee('AI খসড়া')
        ->assertSee('৫-এর মধ্যে ৪')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();

    $page->click('English')
        ->assertSee('Plumber')
        ->assertSee('AI draft')
        ->assertSee('4 of 5')
        ->assertPathIs("/en/occupations/{$this->occupation->slug}")
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});

test('the index leads to the occupation page', function () {
    $page = visit('/en/occupations');

    $page->assertSee('All occupations')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues()
        ->click('Plumber')
        ->assertPathIs("/en/occupations/{$this->occupation->slug}")
        ->assertSee('ISCO-08 unit group 7126');
});
