<?php

use App\Models\User;
use App\Modules\Community\Actions\RecordContributionConsent;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Models\ContributionConsent;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\TestResponse;

/*
 * Signing up needs agreement that contributions are published under CC BY-SA 4.0 (PLAN M8). The
 * agreement is stored with the terms version and the time.
 */

function signup(array $changes = []): TestResponse
{
    return test()->post(route('register.store'), [
        'name' => 'Rahima Begum',
        'email' => 'rahima@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'contribution_terms' => 'on',
        ...$changes,
    ]);
}

it('refuses a signup without consent and creates no account', function (array $changes) {
    signup($changes)->assertSessionHasErrors('contribution_terms');

    $this->assertGuest();
    expect(User::query()->count())->toBe(0)
        ->and(ContributionConsent::query()->count())->toBe(0);
})->with([
    'not ticked' => [['contribution_terms' => null]],
    'refused' => [['contribution_terms' => 'no']],
]);

it('stores the consent with the terms version and the time', function () {
    Date::setTestNow('2026-11-20 08:30:00');

    signup()->assertSessionHasNoErrors();

    $user = User::query()->sole();
    $consent = ContributionConsent::query()->sole();

    expect($consent->user_id)->toBe($user->id)
        ->and($consent->terms_version)->toBe(config('atlas.contribution_terms_version'))
        ->and($consent->accepted_at->toDateTimeString())->toBe('2026-11-20 08:30:00');
});

it('makes every new account a contributor, whatever the form sends', function () {
    signup(['role' => 'maintainer'])->assertSessionHasNoErrors();

    expect(User::query()->sole()->role)->toBe(Role::Contributor);
});

it('explains the missing consent in the visitor\'s language', function () {
    $this->withSession(['locale' => 'en']);
    signup(['contribution_terms' => null])
        ->assertSessionHasErrors(['contribution_terms' => __('auth.register.consent_required', [], 'en')]);
});

it('keeps the first time when the same version is accepted again', function () {
    $user = User::factory()->create();
    Date::setTestNow('2026-11-20 08:00:00');
    app(RecordContributionConsent::class)->handle($user->id);
    Date::setTestNow('2026-11-21 08:00:00');
    app(RecordContributionConsent::class)->handle($user->id);
    app(RecordContributionConsent::class)->handle($user->id, 'next-version');

    expect(ContributionConsent::query()->where('user_id', $user->id)->orderBy('id')->get(['terms_version', 'accepted_at'])
        ->map(fn (ContributionConsent $consent): string => "{$consent->terms_version} {$consent->accepted_at->toDateString()}")
        ->all())->toBe([config('atlas.contribution_terms_version').' 2026-11-20', 'next-version 2026-11-21']);
});

it('shows the contribution terms in both languages', function () {
    $this->get('/contribution-terms')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('contribution-terms')
            ->where('version', config('atlas.contribution_terms_version')));

    $this->get('/en/contribution-terms')->assertOk()->assertSee('CC BY-SA 4.0');
});
