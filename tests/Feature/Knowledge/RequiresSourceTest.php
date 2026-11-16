<?php

use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Exceptions\SourceRequired;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\Source;

// Rule 2: a number or legal requirement needs a source; without one the value stays unknown.

beforeEach(function () {
    $this->field = FieldDefinition::factory()->sensitive()->create();
});

it('rejects a known value without a source and writes nothing', function (string $author) {
    $actor = $author === 'ai' ? aiActor() : personActor();

    expect(fn () => recordValue($this->field, 3, $actor))->toThrow(SourceRequired::class);

    expect(Revision::query()->count())->toBe(0)
        ->and(FieldValue::query()->count())->toBe(0);
})->with(['ai', 'person']);

it('saves a known value with a source and attaches it', function () {
    $source = Source::factory()->create();

    $revision = recordValue($this->field, 3, sourceIds: [$source->id]);

    expect($revision->sources()->pluck('sources.id')->all())->toBe([$source->id])
        ->and($revision->fieldValue->status)->toBe(FieldStatus::Sourced)
        ->and($revision->fieldValue->current_revision_id)->toBe($revision->id);
});

it('saves an unknown value without a source', function (mixed $unknown) {
    $revision = recordValue($this->field, $unknown);

    expect($revision->new_value)->toBeNull()
        ->and($revision->fieldValue->status)->toBe(FieldStatus::AiDraft);
})->with([null, 'unknown']);

it('rejects a source id that does not exist', function () {
    expect(fn () => recordValue($this->field, 3, sourceIds: [999_999]))
        ->toThrow(SourceRequired::class, 'Unknown source ids: 999999.');

    expect(Revision::query()->count())->toBe(0);
});

it('does not ask for a source on a field without requires_source', function () {
    $field = FieldDefinition::factory()->create();

    expect(recordValue($field, 3)->new_value)->toBe(3);
});
