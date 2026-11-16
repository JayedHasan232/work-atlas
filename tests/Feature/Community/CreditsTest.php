<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\ReviewRevision;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Queries\ContributionCredits;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M16: credit is computed from revisions. A person is credited for a change that applied at once
 * or that a reviewer accepted, never for a proposal that is still open or was rejected. AI runs are
 * not people and are never credited.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $this->field = FieldDefinition::query()->where('key', 'physical_workload')->sole();
    $this->page = "/en/occupations/{$this->occupation->slug}";
});

function proposeAs(User $user, int $occupationId, FieldDefinition $field, int $value = 4): Revision
{
    return recordValue($field, $value, Actor::user($user), occupationId: $occupationId);
}

it('credits nobody for an open proposal, and the person once a reviewer accepts it', function () {
    $user = User::factory()->create(['name' => 'Rahima Akter']);
    $revision = proposeAs($user, $this->occupation->id, $this->field);

    $this->get($this->page)->assertInertia(fn (Assert $page) => $page
        ->where('occupation.credits.people', [])
        ->where('occupation.credits.anonymous', 0));

    app(ReviewRevision::class)->handle($revision->id, reviewerActor(), ReviewDecision::Accepted);

    $this->get($this->page)->assertInertia(fn (Assert $page) => $page
        ->where('occupation.credits.people', [[
            'name' => 'Rahima Akter',
            'url' => "/en/contributors/{$user->id}",
            'contributions' => 1,
        ]]));
});

it('does not credit a rejected proposal', function () {
    $user = User::factory()->create();
    $revision = proposeAs($user, $this->occupation->id, $this->field);

    app(ReviewRevision::class)->handle($revision->id, reviewerActor(), ReviewDecision::Rejected);

    expect(app(ContributionCredits::class)->forOccupation($this->occupation->id))->toBe([])
        ->and(app(ContributionCredits::class)->total($user->id))->toBe(0);
});

it('credits a trusted person\'s change that applied at once, most changes first', function () {
    $many = User::factory()->role(Role::Trusted)->create(['name' => 'Many']);
    $one = User::factory()->role(Role::Trusted)->create(['name' => 'One']);
    proposeAs($one, $this->occupation->id, $this->field, 2);
    proposeAs($many, $this->occupation->id, $this->field, 3);
    proposeAs($many, $this->occupation->id, FieldDefinition::query()->where('key', 'mental_workload')->sole(), 3);

    $this->get($this->page)->assertInertia(fn (Assert $page) => $page
        ->where('occupation.credits.people.0.name', 'Many')
        ->where('occupation.credits.people.0.contributions', 2)
        ->where('occupation.credits.people.1.name', 'One')
        ->where('occupation.credits.people.1.contributions', 1));
});

it('never credits an AI run', function () {
    recordValue($this->field, 3, occupationId: $this->occupation->id);

    expect(app(ContributionCredits::class)->forOccupation($this->occupation->id))->toBe([]);
});

it('shows a profile with the person\'s credited changes and occupations, never indexed', function () {
    $user = User::factory()->role(Role::Trusted)->create(['name' => 'Karim Uddin']);
    proposeAs($user, $this->occupation->id, $this->field);
    proposeAs($user, $this->occupation->id, FieldDefinition::query()->where('key', 'mental_workload')->sole(), 2);

    $this->get("/en/contributors/{$user->id}")
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page
            ->component('contributors/show')
            ->where('contributor.name', 'Karim Uddin')
            ->where('contributor.contributions', 2)
            ->has('contributor.occupations', 1)
            ->where('contributor.occupations.0.url', $this->page));
});

it('has no profile for a person who does not exist', function () {
    $this->get('/en/contributors/999999')->assertNotFound();
});
