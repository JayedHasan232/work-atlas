<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Models\FieldDefinition;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M16: a person can contribute without their name being shown. Their changes still count, the
 * occupation page shows them only in the number of anonymous contributors, and they have no profile.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
});

it('lets a signed-in person choose anonymous credit in settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/contributions')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/contributions')->where('anonymous', false));

    $this->actingAs($user)
        ->patch('/settings/contributions', ['anonymous' => true])
        ->assertRedirect('/settings/contributions');

    expect($user->fresh()->anonymous_credit)->toBeTrue();

    $this->actingAs($user)->patch('/settings/contributions', ['anonymous' => false]);

    expect($user->fresh()->anonymous_credit)->toBeFalse();
});

it('keeps the settings page for signed-in people', function () {
    $this->get('/settings/contributions')->assertRedirect('/login');
    $this->patch('/settings/contributions', ['anonymous' => true])->assertRedirect('/login');
});

it('counts an anonymous person without their name, and has no profile for them', function () {
    $user = User::factory()->role(Role::Trusted)->create(['name' => 'Hidden Name', 'anonymous_credit' => true]);
    recordValue(
        FieldDefinition::query()->where('key', 'physical_workload')->sole(),
        4,
        Actor::user($user),
        occupationId: $this->occupation->id,
    );

    $this->get("/en/occupations/{$this->occupation->slug}")
        ->assertDontSee('Hidden Name')
        ->assertInertia(fn (Assert $page) => $page
            ->where('occupation.credits.people', [])
            ->where('occupation.credits.anonymous', 1));

    $this->get("/en/contributors/{$user->id}")->assertNotFound();
});
