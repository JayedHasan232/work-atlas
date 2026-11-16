<?php

use App\Modules\AiPipeline\AiGateway;
use App\Modules\AiPipeline\Exceptions\ProviderError;
use App\Modules\AiPipeline\Models\AiRun;

/*
 * D45: a caller can find an earlier succeeded run with exactly the same input, and reuse it instead
 * of paying for the same call again. Looking makes no run.
 */

beforeEach(function () {
    $this->fake = fakeAi();
    $this->gateway = app(AiGateway::class);
});

it('returns the output of an earlier succeeded run with the same input', function () {
    $this->fake->respondWithJson(['value' => 4, 'confidence' => 0.8]);
    $first = $this->gateway->complete(sampleAiRequest());

    $previous = $this->gateway->previousSuccess(sampleAiRequest());

    expect($previous->runId)->toBe($first->runId)
        ->and($previous->output)->toBe(['value' => 4, 'confidence' => 0.8])
        ->and(AiRun::query()->count())->toBe(1);
});

it('finds nothing for a different input, model or a failed run', function () {
    $this->fake->respondWithJson(['value' => 4, 'confidence' => 0.8]);
    $this->gateway->complete(sampleAiRequest());
    $this->fake->failWith(ProviderError::because('fake', 'down'));
    $this->gateway->complete(sampleAiRequest(['occupation' => 'Plumber']));

    expect($this->gateway->previousSuccess(sampleAiRequest(['occupation' => 'Plumber'])))->toBeNull()
        ->and($this->gateway->previousSuccess(sampleAiRequest(['occupation' => 'Carpenter'])))->toBeNull()
        ->and($this->gateway->previousSuccess(sampleAiRequest(model: 'other-model')))->toBeNull()
        ->and(AiRun::query()->count())->toBe(2);
});
