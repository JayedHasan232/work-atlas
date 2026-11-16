<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Models\Revision;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\TestResponse;

/*
 * PLAN M16 (D62): limits for new accounts. A person can write a set number of changes a day, fewer in
 * their first days; reviewers and above have no daily limit. A new account's links (sources, or a URL
 * in the comment) always wait for a review, even from a trusted person.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $this->url = "/en/occupations/{$this->occupation->slug}/suggest/physical_workload";
    config([
        'atlas.community.new_account_days' => 7,
        'atlas.community.daily_changes' => 3,
        'atlas.community.daily_changes_new_account' => 2,
    ]);
});

function suggestAs(User $user, string $url, array $input = []): TestResponse
{
    return test()->actingAs($user)->post($url, array_replace(['value' => 4, 'interest' => 'none'], $input));
}

function writtenBy(User $user): int
{
    return Revision::query()->where('user_id', $user->id)->count();
}

it('lets a new account write fewer changes a day', function () {
    $user = User::factory()->create();

    suggestAs($user, $this->url)->assertSessionHasNoErrors();
    suggestAs($user, $this->url)->assertSessionHasNoErrors();
    suggestAs($user, $this->url)->assertSessionHasErrors(['value' => __('contribute.errors.limit', [], 'en')]);

    expect(writtenBy($user))->toBe(2);
});

it('lets an older account write more, and counts only the last day', function () {
    $user = User::factory()->create(['created_at' => now()->subDays(30)]);

    foreach (range(1, 3) as $ignored) {
        suggestAs($user, $this->url)->assertSessionHasNoErrors();
    }

    suggestAs($user, $this->url)->assertSessionHasErrors('value');

    Date::setTestNow(now()->addDay()->addMinute());

    suggestAs($user, $this->url)->assertSessionHasNoErrors();

    expect(writtenBy($user))->toBe(4);
});

it('has no daily limit for a reviewer', function () {
    $user = User::factory()->role(Role::Reviewer)->create();

    foreach (range(1, 4) as $ignored) {
        suggestAs($user, $this->url)->assertSessionHasNoErrors();
    }

    expect(writtenBy($user))->toBe(4);
});

it('holds a new trusted account\'s links for a review', function (array $input, bool $newAccount, bool $applied) {
    $user = User::factory()->role(Role::Trusted)->create(['created_at' => $newAccount ? now() : now()->subDays(30)]);

    suggestAs($user, $this->url, $input)
        ->assertSessionHasNoErrors()
        ->assertSessionHas('notice', $applied ? 'contribute.suggest.applied' : 'contribute.suggest.proposed');

    expect(Revision::query()->where('user_id', $user->id)->sole()->proposed)->toBe(! $applied);
})->with([
    'new, with a source' => [['sources' => [['url' => 'https://example.test/a', 'accessed_on' => '2026-11-01']]], true, false],
    'new, with a link in the comment' => [['comment' => 'See www.example.test for prices'], true, false],
    'new, without links' => [['comment' => 'From my own work'], true, true],
    'older, with a source' => [['sources' => [['url' => 'https://example.test/a', 'accessed_on' => '2026-11-01']]], false, true],
]);
