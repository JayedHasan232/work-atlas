<?php

use App\Modules\AiPipeline\Actions\GenerateWave1;
use App\Modules\Knowledge\Enums\CrossCheck;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Queries\ReviewQueue;

/*
 * D50: values the two models disagree on come first in the review queue, then pending proposals;
 * values both models agree on wait for no one.
 */

it('puts model disagreements first, then proposals, and leaves agreed values out', function () {
    $fake = wave1Setup();
    $occupation = listedOccupation();
    $proposal = recordValue(FieldDefinition::query()->where('key', 'formal_training')->sole(), 'required', personActor(), occupationId: $occupation->id);
    $fake->respondWithJson(wave1Output(['physical_workload' => 1, 'work_time' => ['night']]))
        ->respondWithJson(wave1Output(['physical_workload' => 5, 'work_time' => ['day']]));

    app(GenerateWave1::class)->handle($occupation->id);
    $queue = app(ReviewQueue::class)->handle();

    expect(array_map(fn ($item) => $item->fieldKey, $queue))->toBe(['physical_workload', 'work_time', 'formal_training'])
        ->and($queue[0]->crossCheck)->toBe(CrossCheck::Disagreed)
        ->and($queue[0]->currentValue)->toBe(1)
        ->and($queue[0]->proposedValues)->toBe([5])
        ->and($queue[2]->proposedValues)->toBe(['required', 'helpful'])
        ->and($proposal->proposed)->toBeTrue()
        ->and(array_map(fn ($item) => $item->fieldKey, app(ReviewQueue::class)->handle(CrossCheck::Disagreed)))->toBe(['physical_workload', 'work_time']);
});
