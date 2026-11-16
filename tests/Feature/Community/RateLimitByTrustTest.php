<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Models\Revision;
use Illuminate\Testing\TestResponse;

/*
 * PLAN M18 (D68): the daily limit on changes follows the trust level. A new account gets the fewest,
 * a contributor the default, a trusted contributor more, and reviewers and above have none.
 */

beforeEach(function () {
    wave1Setup();
    $this->url = '/en/occupations/'.listedOccupation()->slug.'/suggest/physical_workload';
    config([
        'atlas.community.new_account_days' => 7,
        'atlas.community.daily_changes_new_account' => 1,
        'atlas.community.daily_changes' => 2,
        'atlas.community.daily_changes_trusted' => 4,
    ]);
});

function trustSuggest(User $user, string $url): TestResponse
{
    return test()->actingAs($user)->post($url, ['value' => 4, 'interest' => 'none']);
}

it('gives each trust level its daily limit', function (Role $role, bool $newAccount, int $limit) {
    $user = User::factory()->role($role)->create(['created_at' => $newAccount ? now() : now()->subDays(30)]);

    foreach (range(1, $limit) as $ignored) {
        trustSuggest($user, $this->url)->assertSessionHasNoErrors();
    }

    trustSuggest($user, $this->url)->assertSessionHasErrors(['value' => __('contribute.errors.limit', [], 'en')]);

    expect(Revision::query()->where('user_id', $user->id)->count())->toBe($limit);
})->with([
    'new contributor' => [Role::Contributor, true, 1],
    'new trusted contributor' => [Role::Trusted, true, 1],
    'contributor' => [Role::Contributor, false, 2],
    'trusted contributor' => [Role::Trusted, false, 4],
]);

it('has no daily limit for reviewers and above', function (Role $role) {
    $user = User::factory()->role($role)->create(['created_at' => now()]);

    foreach (range(1, 6) as $ignored) {
        trustSuggest($user, $this->url)->assertSessionHasNoErrors();
    }

    expect(Revision::query()->where('user_id', $user->id)->count())->toBe(6);
})->with([Role::Reviewer, Role::Expert, Role::Maintainer]);
