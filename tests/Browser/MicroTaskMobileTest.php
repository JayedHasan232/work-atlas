<?php

use App\Models\User;
use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Community\Models\VerificationAnswer;

// PLAN M17: on a phone, a signed-in person answers one short question at a time, without JavaScript
// errors or accessibility issues, and sees when nothing is left.

beforeEach(function () {
    wave1Setup();
});

test('a person checks facts on a phone', function () {
    $occupation = listedOccupation();
    taskFor('physical_workload', 4, occupation: $occupation);
    taskFor('mental_workload', 2, occupation: $occupation);
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit('/en/tasks')->on()->mobile();

    $page->assertSee('Is this right?')
        ->assertSee('Plumber')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues()
        ->assertSeeIn('[data-test="answer-agree"]', 'Yes, that is right')
        ->click('[data-test="answer-agree"]')
        ->assertSee('Thank you. Here is the next question.')
        ->click('[data-test="answer-skip"]')
        ->assertSee('Skipped. Here is the next question.')
        ->assertSee('No questions left for you right now.')
        ->assertNoAccessibilityIssues()
        ->assertNoJavaScriptErrors();

    expect(VerificationAnswer::query()->where('user_id', $user->id)->orderBy('id')->pluck('answer')->all())
        ->toBe([TaskAnswer::Agree, TaskAnswer::Skip]);
});
