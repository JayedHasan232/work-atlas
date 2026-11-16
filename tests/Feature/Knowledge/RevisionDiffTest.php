<?php

use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Queries\RevisionDiff;

it('marks the removed and added lines of a revision', function () {
    $field = FieldDefinition::factory()->create(['type' => 'multi_enum', 'options' => ['day', 'night', 'shift'], 'anchors' => null]);
    $first = recordValue($field, ['day', 'night']);
    $second = app(RecordRevision::class)->handle(new RevisionInput(
        occupationId: $first->fieldValue->occupation_id,
        fieldDefinitionId: $field->id,
        value: ['day', 'shift'],
        actor: aiActor(),
        confidence: 0.5,
    ));

    expect(app(RevisionDiff::class)->handle($second->id))->toBe([
        ['op' => ' ', 'line' => '['],
        ['op' => ' ', 'line' => '    "day",'],
        ['op' => '-', 'line' => '    "night"'],
        ['op' => '+', 'line' => '    "shift"'],
        ['op' => ' ', 'line' => ']'],
    ]);
});

it('shows a first value as added lines after null', function () {
    $revision = recordValue(FieldDefinition::factory()->create(), 3);

    expect(app(RevisionDiff::class)->handle($revision->id))->toBe([
        ['op' => '-', 'line' => 'null'],
        ['op' => '+', 'line' => '3'],
    ]);
});
