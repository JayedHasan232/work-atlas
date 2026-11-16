<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Models\DiscussionPost;
use App\Modules\Community\Models\DiscussionThread;

// PLAN M19: on a phone, a contributor opens a discussion on an occupation's talk page and another
// person replies, without JavaScript errors or accessibility issues.

beforeEach(function () {
    wave1Setup();
});

test('a contributor starts a discussion and someone replies', function () {
    $occupation = listedOccupation();
    $this->actingAs(User::factory()->role(Role::Contributor)->create(['created_at' => now()->subDays(30)]));

    $page = visit('/en/occupations/'.$occupation->slug.'/talk')->on()->mobile();

    $page->assertSee('Talk: Plumber')
        ->assertSee('No discussions yet.')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues()
        ->type('[data-test="talk-title"]', 'Night calls in winter')
        ->type('[data-test="talk-body"]', 'Many plumbers take night calls when pipes freeze.')
        ->click('[data-test="talk-start"]')
        ->assertSee('Your discussion has started.')
        ->assertSee('Many plumbers take night calls when pipes freeze.')
        ->assertNoAccessibilityIssues()
        ->type('[data-test="talk-reply"]', 'Same in Sylhet.')
        ->click('[data-test="talk-send"]')
        ->assertSee('Your reply has been added.')
        ->assertSee('Same in Sylhet.')
        ->assertNoJavaScriptErrors();

    expect(DiscussionThread::query()->sole()->posts_count)->toBe(2)
        ->and(DiscussionPost::query()->count())->toBe(2);
});
