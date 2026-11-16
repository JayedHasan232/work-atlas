<?php

use App\Modules\Knowledge\Models\FieldDefinition;

// PLAN M20: on a phone, a person answers a few questions, sees the matches with the reasons and
// the limits, and picks two to compare, without JavaScript errors or accessibility issues.

beforeEach(function () {
    wave1Setup();
});

test('a person answers the guide on a phone and compares two matches', function () {
    $plumber = listedOccupation();
    $tailor = listedOccupation([
        'bn' => [['name' => 'দর্জি', 'type' => 'primary']],
        'en' => [['name' => 'Tailor', 'type' => 'primary']],
    ]);
    $partTime = FieldDefinition::query()->where('key', 'part_time_possible')->firstOrFail();
    recordValue($partTime, 'yes', occupationId: $tailor->id);
    recordValue($partTime, 'sometimes', occupationId: $plumber->id);

    $page = visit('/en/guide')->on()->mobile();

    $page->assertSee('How much time can you give?')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues()
        ->click('[data-test="answer-hours-part_time"]')
        ->click('[data-test="answer-area-rural"]')
        ->click('[data-test="guide-submit"]')
        ->assertSee('Tailor')
        ->assertSee('Plumber')
        ->assertSee('Part-time is possible only sometimes.')
        ->assertSee('It is not a promise of work or income.')
        ->assertPresent('[data-test="guide-reason"]')
        ->assertPresent('[data-test="guide-limits"]')
        ->assertNoAccessibilityIssues()
        ->click('[data-test="compare-choose-'.$tailor->slug.'"]')
        ->click('[data-test="compare-choose-'.$plumber->slug.'"]')
        ->click('[data-test="compare-selected"]')
        ->assertPresent('[data-test="compare-table"]')
        ->assertSee('Compare occupations')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});
