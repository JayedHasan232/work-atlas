<?php

use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Exceptions\InvalidFieldValue;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\Source;
use App\Modules\Taxonomy\Models\Occupation;

// Rule 3: AI output enters as ai_draft, or sourced when a source was captured.

beforeEach(function () {
    $this->field = FieldDefinition::factory()->create();
});

it('enters an AI value without a source as ai_draft', function () {
    $revision = recordValue($this->field, 3);
    $slot = $revision->fieldValue;

    expect($slot->status)->toBe(FieldStatus::AiDraft)
        ->and($slot->current_revision_id)->toBe($revision->id)
        ->and($revision->proposed)->toBeFalse()
        ->and($revision->confidence)->toBe(0.8)
        ->and($revision->old_value)->toBeNull()
        ->and($revision->replaces_revision_id)->toBeNull()
        ->and($revision->old_status)->toBeNull();
});

it('enters an AI value with a source as sourced', function () {
    $revision = recordValue($this->field, 3, sourceIds: [Source::factory()->create()->id]);

    expect($revision->fieldValue->status)->toBe(FieldStatus::Sourced);
});

it('keeps an unknown value at ai_draft even with a source', function () {
    $revision = recordValue($this->field, 'unknown', sourceIds: [Source::factory()->create()->id]);

    expect($revision->fieldValue->status)->toBe(FieldStatus::AiDraft);
});

it('records what a new value replaced', function () {
    $first = recordValue($this->field, 2, sourceIds: [Source::factory()->create()->id]);
    $second = recordValue($this->field, 4, occupationId: $first->fieldValue->occupation_id);

    expect($second->field_value_id)->toBe($first->field_value_id)
        ->and($second->old_value)->toBe(2)
        ->and($second->replaces_revision_id)->toBe($first->id)
        ->and($second->old_status)->toBe(FieldStatus::Sourced)
        ->and($second->fieldValue->status)->toBe(FieldStatus::AiDraft);
});

it('records a person\'s value as a proposal until roles exist', function () {
    $revision = recordValue($this->field, 3, personActor());
    $slot = $revision->fieldValue;

    expect($revision->proposed)->toBeTrue()
        ->and($revision->ai_run_id)->toBeNull()
        ->and($revision->confidence)->toBeNull()
        ->and($slot->current_revision_id)->toBeNull()
        ->and($slot->review_priority)->toBe(FieldValue::PRIORITY_PENDING_PROPOSAL);
});

it('needs a confidence from an AI run and none from a person', function (string $author, ?float $confidence) {
    $actor = $author === 'ai' ? aiActor() : personActor();

    expect(fn () => app(RecordRevision::class)->handle(
        new RevisionInput(
            occupationId: Occupation::factory()->create()->id,
            fieldDefinitionId: $this->field->id,
            value: 3,
            actor: $actor,
            confidence: $confidence,
        ),
    ))->toThrow(InvalidFieldValue::class);

    expect(Revision::query()->count())->toBe(0);
})->with([
    'AI without a confidence' => ['ai', null],
    'AI above 1' => ['ai', 1.5],
    'AI below 0' => ['ai', -0.1],
    'person with a confidence' => ['person', 0.5],
]);

it('writes nothing for a value that does not fit the field', function () {
    expect(fn () => recordValue($this->field, 6))->toThrow(InvalidFieldValue::class);

    expect(Revision::query()->count())->toBe(0)
        ->and(FieldValue::query()->count())->toBe(0);
});
