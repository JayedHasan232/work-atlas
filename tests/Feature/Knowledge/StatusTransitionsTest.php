<?php

use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Exceptions\StatusTransitionNotAllowed;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Source;

/**
 * Every move a person may make. Spelled out here, apart from FieldStatus, so a change to the table
 * in the code has to be made in this test too.
 */
const ALLOWED_STATUS_MOVES = [
    'ai_draft' => ['sourced', 'community_supported', 'verified', 'disputed'],
    'sourced' => ['community_supported', 'verified', 'disputed'],
    'community_supported' => ['verified', 'disputed'],
    'verified' => ['disputed'],
    'disputed' => ['sourced', 'community_supported', 'verified'],
];

dataset('status moves', function () {
    foreach (FieldStatus::cases() as $from) {
        foreach (FieldStatus::cases() as $to) {
            $allowed = in_array($to->value, ALLOWED_STATUS_MOVES[$from->value], true);

            yield "{$from->value} → {$to->value} ".($allowed ? 'allowed' : 'forbidden') => [$from, $to, $allowed];
        }
    }
});

function slotWithSourcedValue(FieldStatus $status): FieldValue
{
    $slot = recordValue(FieldDefinition::factory()->create(), 3, sourceIds: [Source::factory()->create()->id])->fieldValue;
    $slot->forceFill(['status' => $status])->save();

    return $slot;
}

it('allows exactly the moves in the transition table', function (FieldStatus $from, FieldStatus $to, bool $allowed) {
    $slot = slotWithSourcedValue($from);
    $change = fn () => app(ChangeStatus::class)->handle($slot->id, $to, personActor());

    if ($allowed) {
        expect($change()->status)->toBe($to)
            ->and($slot->fresh()->status)->toBe($to);
    } else {
        expect($change)->toThrow(StatusTransitionNotAllowed::class);
        expect($slot->fresh()->status)->toBe($from);
    }
})->with('status moves');

it('does not mark a value without a source as sourced', function () {
    $slot = recordValue(FieldDefinition::factory()->create(), 3)->fieldValue;

    expect(fn () => app(ChangeStatus::class)->handle($slot->id, FieldStatus::Sourced, personActor()))
        ->toThrow(StatusTransitionNotAllowed::class, 'no source');
});

it('does not change the status of a slot without a current value', function () {
    $slot = recordValue(FieldDefinition::factory()->create(), 3, personActor())->fieldValue;

    expect(fn () => app(ChangeStatus::class)->handle($slot->id, FieldStatus::Verified, personActor()))
        ->toThrow(StatusTransitionNotAllowed::class, 'no current value');
});

it('never lets an AI run change a status', function () {
    $slot = slotWithSourcedValue(FieldStatus::AiDraft);

    expect(fn () => app(ChangeStatus::class)->handle($slot->id, FieldStatus::Verified, aiActor()))
        ->toThrow(StatusTransitionNotAllowed::class, 'AI run');

    expect($slot->fresh()->status)->toBe(FieldStatus::AiDraft);
});
