<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Queries\WaveFields;
use App\Modules\Publishing\Filament\Widgets\Gate1Widget;
use App\Modules\Publishing\Queries\Gate1Progress;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\Occupation;
use Filament\Facades\Filament;
use Livewire\Livewire;

/*
 * PLAN M23: the Gate 1 dashboard in /admin shows listed occupations, the share of Wave 1 values at
 * community_supported or better, and the people active in the last 30 days. Its numbers match the
 * database.
 */

function wave1Field(string $key): FieldDefinition
{
    return FieldDefinition::query()->where('key', $key)->orderByDesc('version')->firstOrFail();
}

beforeEach(function () {
    wave1Setup();
    $this->maintainer = personActor(Role::Maintainer);
    $this->first = listedOccupation();
    $this->second = listedOccupation(['en' => [['name' => 'Tailor', 'type' => 'primary']]]);
    $candidate = occupationNamed(['en' => [['name' => 'Candidate work', 'type' => 'primary']]]);

    // First occupation: one AI draft, one value a maintainer wrote (enters community_supported).
    recordValue(wave1Field('physical_workload'), 3, occupationId: $this->first->id);
    recordValue(wave1Field('mental_workload'), 2, $this->maintainer, occupationId: $this->first->id);

    // Second occupation: one verified, one disputed.
    $verified = recordValue(wave1Field('seasonal'), 'year_round', occupationId: $this->second->id);
    app(ChangeStatus::class)->handle($verified->field_value_id, FieldStatus::Verified, $this->maintainer);
    $disputed = recordValue(wave1Field('work_time'), ['day'], occupationId: $this->second->id);
    app(ChangeStatus::class)->handle($disputed->field_value_id, FieldStatus::Disputed, $this->maintainer);

    // A candidate is not listed: its value does not count.
    recordValue(wave1Field('seasonal'), 'year_round', $this->maintainer, occupationId: $candidate->id);
});

it('counts listed occupations and Wave 1 slots like the database does', function () {
    $status = app(Gate1Progress::class)->handle();
    $keys = count(app(WaveFields::class)->handle(1));

    $listed = Occupation::query()->whereIn('publish_state', [PublishState::Listed, PublishState::Published])->count();
    $supportedInDb = FieldValue::query()
        ->whereIn('occupation_id', [$this->first->id, $this->second->id])
        ->whereIn('status', [FieldStatus::CommunitySupported, FieldStatus::Verified])
        ->count();

    expect($status->listedOccupations)->toBe($listed)->toBe(2)
        ->and($status->wave1->slots)->toBe(2 * $keys)
        ->and($status->wave1->filled)->toBe(4)
        ->and($status->wave1->supported)->toBe($supportedInDb)->toBe(2)
        ->and($status->wave1->supportedPercent())->toBe(round(2 / (2 * $keys) * 100, 1));
});

it('does not count a proposal that nobody accepted as a filled slot', function () {
    recordValue(wave1Field('formal_training'), 'helpful', personActor(), occupationId: $this->first->id);

    expect(app(Gate1Progress::class)->handle()->wave1->filled)->toBe(4);
});

it('counts each person active in the last 30 days once, and those outside the core team', function () {
    // Wrote a proposal 40 days ago: not active.
    $this->travel(-40)->days();
    recordValue(wave1Field('formal_training'), 'helpful', personActor(), occupationId: $this->second->id);
    $this->travelBack();

    // A contributor answers a fact-check question and also proposes a change: one person.
    $contributor = User::factory()->create();
    answerTask(taskFor('time_to_learn', 'months_3_12', occupation: $this->first), TaskAnswer::Agree, $contributor);
    recordValue(wave1Field('formal_training'), 'helpful', Actor::user($contributor), occupationId: $this->first->id);

    $active = app(Gate1Progress::class)->handle()->activeContributors;

    // The maintainer (core team) and the contributor.
    expect($active->all)->toBe(2)
        ->and($active->outsideCoreTeam)->toBe(1);
});

it('shows the numbers on the admin dashboard', function () {
    $this->actingAs(User::factory()->role(Role::Maintainer)->create());
    app()->setLocale('en');
    $keys = count(app(WaveFields::class)->handle(1));
    $percent = round(2 / (2 * $keys) * 100, 1);

    Livewire::test(Gate1Widget::class)
        ->assertSee('Gate 1 → Phase 2')
        ->assertSee('Listed occupations')
        ->assertSee("{$percent}%")
        ->assertSee('2 supported, 4 filled, of '.(2 * $keys).' slots')
        ->assertSee('Active contributors');
});

it('is on the admin dashboard', function () {
    expect(Filament::getPanel('admin')->getWidgets())->toContain(Gate1Widget::class);
});
