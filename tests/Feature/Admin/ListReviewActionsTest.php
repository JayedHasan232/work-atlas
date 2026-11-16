<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Taxonomy\Actions\DetectDuplicates;
use App\Modules\Taxonomy\Actions\RejectCandidate;
use App\Modules\Taxonomy\Enums\DuplicateStatus;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Filament\Resources\DuplicateCandidates\Pages\ListDuplicateCandidates;
use App\Modules\Taxonomy\Filament\Resources\Occupations\Pages\ListOccupations;
use App\Modules\Taxonomy\Filament\Resources\Occupations\Pages\ViewOccupation;
use App\Modules\Taxonomy\Models\DuplicateCandidate;
use App\Modules\Taxonomy\Models\IscoGroup;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

/*
 * The list-review screens in /admin (PLAN M11) call the Taxonomy actions; a broken rule is shown as a
 * notification and nothing is written.
 */

beforeEach(function () {
    FieldDefinition::factory()->names()->create();
    IscoGroup::factory()->create(['code' => '7126']);
    $this->actingAs(User::factory()->role(Role::Maintainer)->create());
    $this->candidate = occupationNamed([
        'bn' => [['name' => 'প্লাম্বার', 'type' => 'primary']],
        'en' => [['name' => 'Plumber', 'type' => 'primary']],
    ]);
});

it('approves a candidate from the list', function () {
    Livewire::test(ListOccupations::class)
        ->callAction(TestAction::make('approve')->table($this->candidate), ['note' => 'Common work.'])
        ->assertHasNoFormErrors()
        ->assertNotified(__('admin.review.done'));

    expect($this->candidate->fresh()->publish_state)->toBe(PublishState::Listed);
});

it('needs a reason to reject', function () {
    Livewire::test(ViewOccupation::class, ['record' => $this->candidate->id])
        ->callAction('reject', ['note' => ''])
        ->assertHasFormErrors(['note' => 'required']);

    expect($this->candidate->fresh()->publish_state)->toBe(PublishState::Candidate);
});

it('edits names through a revision', function () {
    Livewire::test(ViewOccupation::class, ['record' => $this->candidate->id])
        ->callAction('editNames', [
            'bn' => [['name' => 'প্লাম্বার', 'type' => 'primary'], ['name' => 'নলমিস্ত্রি', 'type' => 'local']],
            'en' => [['name' => 'Plumber', 'type' => 'primary']],
        ])
        ->assertNotified(__('admin.review.done'));

    expect(searchNamesOf($this->candidate->id))->toContain(['bn', 'নলমিস্ত্রি', 'local', null]);
});

it('shows a broken rule instead of writing', function () {
    app(RejectCandidate::class)->handle($this->candidate->id, auth()->user(), 'Not a separate occupation.');
    $other = occupationNamed(['bn' => [['name' => 'রাজমিস্ত্রি', 'type' => 'primary']], 'en' => [['name' => 'Mason', 'type' => 'primary']]]);

    Livewire::test(ViewOccupation::class, ['record' => $other->id])
        ->callAction('merge', ['into' => $this->candidate->id])
        ->assertNotified(__('admin.review.failed'));

    expect($other->fresh()->publish_state)->toBe(PublishState::Candidate);
});

it('keeps a duplicate pair separate', function () {
    occupationNamed(['bn' => [['name' => "প্লাম্বার\u{200C}", 'type' => 'primary']], 'en' => [['name' => 'Pipe fitter', 'type' => 'primary']]]);
    app(DetectDuplicates::class)->handle();
    $pair = DuplicateCandidate::query()->sole();

    Livewire::test(ListDuplicateCandidates::class)
        ->callAction(TestAction::make('keepSeparate')->table($pair))
        ->assertNotified(__('admin.review.done'));

    expect($pair->fresh()->status)->toBe(DuplicateStatus::KeptSeparate);
});
