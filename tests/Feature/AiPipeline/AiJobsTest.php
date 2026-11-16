<?php

use App\Modules\AiPipeline\AiGateway;
use App\Modules\AiPipeline\Enums\AiRunStatus;
use App\Modules\AiPipeline\Jobs\PollAiBatch;
use App\Modules\AiPipeline\Jobs\RunAiCompletion;
use App\Modules\AiPipeline\Models\AiRun;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->fake = fakeAi();
});

it('runs a completion on the queue, once', function () {
    $this->fake->respondWithJson(['value' => 5, 'confidence' => 0.9]);

    RunAiCompletion::dispatch(sampleAiRequest());

    expect(AiRun::query()->sole()->status)->toBe(AiRunStatus::Succeeded)
        ->and((new RunAiCompletion(sampleAiRequest()))->tries)->toBe(1);
});

it('puts a batch poll back on the queue while the provider is working', function () {
    $batchId = app(AiGateway::class)->submitBatch([sampleAiRequest()])->batchId;
    $this->fake->holdBatches(1);
    $job = (new PollAiBatch((string) $batchId))->withFakeQueueInteractions();

    $job->handle(app(AiGateway::class));
    $job->assertReleased(delay: 60);

    $this->fake->respondWithJson(['value' => 5, 'confidence' => 0.9]);
    $again = (new PollAiBatch((string) $batchId))->withFakeQueueInteractions();
    $again->handle(app(AiGateway::class));

    $again->assertNotReleased();
    expect(AiRun::query()->sole()->status)->toBe(AiRunStatus::Succeeded);
});

it('can be queued, so callers never call a model inside a web request', function () {
    Queue::fake();

    RunAiCompletion::dispatch(sampleAiRequest());

    Queue::assertPushed(RunAiCompletion::class);
    expect(AiRun::query()->count())->toBe(0);
});
