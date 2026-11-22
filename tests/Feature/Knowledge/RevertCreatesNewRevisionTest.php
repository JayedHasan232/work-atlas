<?php

use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Actions\RevertRevision;
use App\Modules\Knowledge\Actions\ReviewRevision;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Exceptions\RevertNotAllowed;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\Source;

// Rule 5: a revert is a new revision; the reverted one stays in the history unchanged.

beforeEach(function () {
    $this->field = FieldDefinition::factory()->sensitive()->create();
    $this->source = Source::factory()->create();

    // A sourced value the community supports, then an AI value with another source on top of it.
    $this->first = recordValue($this->field, 2, sourceIds: [$this->source->id]);
    $this->slot = $this->first->fieldValue;
    app(ChangeStatus::class)->handle($this->slot->id, FieldStatus::CommunitySupported, reviewerActor());

    $this->second = recordValue($this->field, 4, sourceIds: [Source::factory()->create()->id], occupationId: $this->slot->occupation_id);
});

function revert(int $revisionId, mixed $actor = null): Revision
{
    return app(RevertRevision::class)->handle($revisionId, $actor ?? reviewerActor(), 'Back to the checked value.');
}

it('restores the replaced value, its sources and its status in a new revision', function () {
    $before = Revision::query()->count();

    $revert = revert($this->second->id);
    $slot = $this->slot->fresh();

    expect(Revision::query()->count())->toBe($before + 1)
        ->and($revert->reverts_revision_id)->toBe($this->second->id)
        ->and($revert->old_value)->toBe(4)
        ->and($revert->new_value)->toBe(2)
        ->and($revert->sources()->pluck('sources.id')->all())->toBe([$this->source->id])
        ->and($slot->current_revision_id)->toBe($revert->id)
        ->and($slot->status)->toBe(FieldStatus::CommunitySupported);

    // The reverted revision is still there, unchanged.
    expect($this->second->fresh()->only(['new_value', 'old_value', 'proposed']))
        ->toBe(['new_value' => 4, 'old_value' => 2, 'proposed' => false]);
});

it('reverts a revert', function () {
    $revert = revert($this->second->id);
    $again = revert($revert->id);
    $slot = $this->slot->fresh();

    expect($again->new_value)->toBe(4)
        ->and($slot->current_revision_id)->toBe($again->id)
        ->and($slot->status)->toBe(FieldStatus::Sourced);
});

it('restores what an accepted proposal replaced', function () {
    $proposal = recordValue($this->field, 5, personActor(), [$this->source->id], $this->slot->occupation_id);
    app(ReviewRevision::class)->handle($proposal->id, reviewerActor(), ReviewDecision::Accepted);

    $revert = revert($proposal->id);
    $slot = $this->slot->fresh();

    expect($revert->new_value)->toBe(4)
        ->and($slot->current_revision_id)->toBe($revert->id)
        ->and($slot->status)->toBe(FieldStatus::Sourced);
});

it('leaves a slot unknown when its first value is reverted', function () {
    $field = FieldDefinition::factory()->create();
    $only = recordValue($field, 3);

    $revert = revert($only->id);

    expect($revert->new_value)->toBeNull()
        ->and($revert->fieldValue->status)->toBe(FieldStatus::AiDraft)
        ->and($revert->fieldValue->current_revision_id)->toBe($revert->id);
});

it('reverts only the current value', function () {
    expect(fn () => revert($this->first->id))->toThrow(RevertNotAllowed::class, 'not the current value');

    expect($this->slot->fresh()->current_revision_id)->toBe($this->second->id);
});

it('never lets an AI run revert', function () {
    expect(fn () => revert($this->second->id, aiActor()))->toThrow(RevertNotAllowed::class, 'AI run');
});

it('does not let a trusted person bring a verified value back by undoing a revert', function () {
    $field = FieldDefinition::factory()->create();
    $first = recordValue($field, 2);
    $slot = $first->fieldValue;
    $second = recordValue($field, 4, occupationId: $slot->occupation_id);
    app(ChangeStatus::class)->handle($slot->id, FieldStatus::Verified, reviewerActor());

    // A reviewer undoes the verified value; the slot is back at the AI draft.
    $revert = revert($second->id);
    expect($slot->fresh()->status)->toBe(FieldStatus::AiDraft);

    // Undoing that revert would restore "verified". Only a reviewer may.
    expect(fn () => revert($revert->id, personActor(Role::Trusted)))->toThrow(RevertNotAllowed::class);
    expect($slot->fresh()->status)->toBe(FieldStatus::AiDraft);

    revert($revert->id);
    expect($slot->fresh()->status)->toBe(FieldStatus::Verified);
});
