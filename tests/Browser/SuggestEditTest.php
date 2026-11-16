<?php

use App\Models\User;
use App\Modules\Community\Models\ErrorReport;
use App\Modules\Knowledge\Models\Revision;

// PLAN M16: from an occupation page a signed-in contributor suggests a new value for one field, sees
// the before and after, and sends it for review; a reader reports a mistake without an account. Both
// in a real browser, without JavaScript errors or accessibility issues.

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
});

test('a contributor suggests a change and sees it wait for review', function () {
    $user = User::factory()->create(['created_at' => now()->subDays(30)]);
    $this->actingAs($user);

    $page = visit("/en/occupations/{$this->occupation->slug}/suggest/physical_workload");

    $page->assertSee('Suggest a change')
        ->assertSee('Physical workload')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues()
        ->radio('value', '4')
        ->assertSeeIn('[data-test="diff-preview"]', 'Frequent lifting')
        ->fill('comment', 'I have done this work for years.')
        ->radio('interest', 'none')
        ->press('Send suggestion')
        ->assertPathIs("/en/occupations/{$this->occupation->slug}")
        ->assertSee('Thank you. Your suggestion waits for a review.')
        ->assertNoJavaScriptErrors();

    expect(Revision::query()->where('user_id', $user->id)->sole())
        ->proposed->toBeTrue()
        ->new_value->toBe(4);
});

test('a reader reports a mistake without an account', function () {
    $page = visit("/en/occupations/{$this->occupation->slug}");

    $page->click('Report a mistake')
        ->assertPathIs("/en/occupations/{$this->occupation->slug}/report")
        ->assertNoAccessibilityIssues()
        ->fill('message', 'The group looks wrong.')
        ->press('Send report')
        ->assertPathIs("/en/occupations/{$this->occupation->slug}")
        ->assertSee('Thank you. Your report is in the review queue.')
        ->assertNoJavaScriptErrors();

    expect(ErrorReport::query()->sole()->message)->toBe('The group looks wrong.');
});
