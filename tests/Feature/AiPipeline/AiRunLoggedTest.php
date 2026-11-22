<?php

use App\Modules\AiPipeline\AiGateway;
use App\Modules\AiPipeline\Data\CompletionRequest;
use App\Modules\AiPipeline\Data\CompletionResult;
use App\Modules\AiPipeline\Drivers\AiDriver;
use App\Modules\AiPipeline\Enums\AiRunStatus;
use App\Modules\AiPipeline\Exceptions\ProviderError;
use App\Modules\AiPipeline\Exceptions\UnknownProvider;
use App\Modules\AiPipeline\Models\AiRun;

/*
 * Every AI call creates an ai_runs row, failures included (CLAUDE.md, "Rules enforced in code").
 */

beforeEach(function () {
    $this->fake = fakeAi();
    $this->gateway = app(AiGateway::class);
});

describe('single calls', function () {
    it('logs a successful call with its output, tokens, cost and input hash', function () {
        $this->fake->respondWithJson(['value' => 4, 'confidence' => 0.7], inputTokens: 120, outputTokens: 30);

        $result = $this->gateway->complete(sampleAiRequest());
        $run = AiRun::query()->sole();

        expect($result->succeeded())->toBeTrue()
            ->and($result->runId)->toBe($run->id)
            ->and($result->output)->toBe(['value' => 4, 'confidence' => 0.7])
            ->and($run->status)->toBe(AiRunStatus::Succeeded)
            ->and($run->provider)->toBe('fake')
            ->and($run->model)->toBe('fake-model')
            ->and($run->prompt_key)->toBe('test/sample')
            ->and($run->prompt_version)->toBe(1)
            ->and($run->input_hash)->toMatch('/^[0-9a-f]{64}$/')
            ->and($run->raw_output)->toBe('{"value":4,"confidence":0.7}')
            ->and($run->parsed_output)->toBe(['value' => 4, 'confidence' => 0.7])
            ->and($run->input_tokens)->toBe(120)
            ->and($run->output_tokens)->toBe(30)
            ->and($run->cost_usd)->toBe('0.000000')
            ->and($run->error)->toBeNull()
            ->and($run->started_at)->not->toBeNull()
            ->and($run->finished_at)->not->toBeNull();
    });

    it('sends the rendered prompt, the schema and the prompt settings to the provider', function () {
        $this->fake->respondWithJson(['value' => null, 'confidence' => 0]);

        $this->gateway->complete(sampleAiRequest(['occupation' => 'নলমিস্ত্রি', 'isco_code' => '7126']));
        $sent = $this->fake->requests()[0];

        expect($sent->prompt)->toContain('নলমিস্ত্রি')->toContain('7126')->not->toContain('{{')
            ->and($sent->id)->toBe((string) AiRun::query()->sole()->id)
            ->and(json_decode($sent->schemaJson, true)['required'])->toBe(['value', 'confidence'])
            ->and($sent->maxOutputTokens)->toBe(200)
            ->and($sent->temperature)->toBe(0.0);
    });

    it('gives the same input the same hash and a different input another', function () {
        $this->fake->respondWithJson(['value' => 1, 'confidence' => 1])
            ->respondWithJson(['value' => 1, 'confidence' => 1])
            ->respondWithJson(['value' => 1, 'confidence' => 1]);

        $this->gateway->complete(sampleAiRequest());
        $this->gateway->complete(sampleAiRequest());
        $this->gateway->complete(sampleAiRequest(['isco_code' => '7412']));
        $hashes = AiRun::query()->orderBy('id')->pluck('input_hash')->all();

        expect($hashes[0])->toBe($hashes[1])->and($hashes[2])->not->toBe($hashes[0]);
    });

    it('logs a provider error as a failed run', function () {
        $this->fake->failWith(ProviderError::because('fake', 'the request was refused'));

        $result = $this->gateway->complete(sampleAiRequest());
        $run = AiRun::query()->sole();

        expect($result->succeeded())->toBeFalse()
            ->and($result->output)->toBeNull()
            ->and($run->status)->toBe(AiRunStatus::Failed)
            ->and($run->error)->toContain('the request was refused')
            ->and($run->raw_output)->toBeNull()
            ->and($run->finished_at)->not->toBeNull();
    });

    it('keeps the raw output but fails a run whose output does not match the schema', function (string $raw, string $error) {
        $this->fake->respond($raw);

        $result = $this->gateway->complete(sampleAiRequest());
        $run = AiRun::query()->sole();

        expect($result->succeeded())->toBeFalse()
            ->and($run->status)->toBe(AiRunStatus::Failed)
            ->and($run->raw_output)->toBe($raw)
            ->and($run->parsed_output)->toBeNull()
            ->and($run->input_tokens)->toBe(100)
            ->and($run->error)->toContain($error);
    })->with([
        'not JSON' => ['Sure! Here is the answer: 4', 'not a JSON object'],
        'a JSON list' => ['[4, 0.7]', 'not a JSON object'],
        'a missing field' => ['{"value": 4}', 'does not match the schema'],
        'an extra field' => ['{"value": 4, "confidence": 0.7, "income": 15000}', 'does not match the schema'],
        'confidence out of range' => ['{"value": 4, "confidence": 1.5}', 'does not match the schema'],
    ]);

    it('retries a retryable error and logs one run', function () {
        $this->fake->failWith(ProviderError::because('fake', 'rate limited', retryable: true))
            ->failWith(ProviderError::because('fake', 'overloaded', retryable: true))
            ->respondWithJson(['value' => 2, 'confidence' => 0.5]);

        $result = $this->gateway->complete(sampleAiRequest());

        expect($result->succeeded())->toBeTrue()
            ->and($this->fake->requests())->toHaveCount(3)
            ->and(AiRun::query()->count())->toBe(1);
    });

    it('gives up after the configured retries and logs the last error', function () {
        foreach (range(1, 3) as $attempt) {
            $this->fake->failWith(ProviderError::because('fake', "overloaded {$attempt}", retryable: true));
        }

        $result = $this->gateway->complete(sampleAiRequest());

        expect($this->fake->requests())->toHaveCount(3)
            ->and($result->error)->toContain('overloaded 3')
            ->and(AiRun::query()->sole()->status)->toBe(AiRunStatus::Failed);
    });

    it('does not retry an error that is not retryable', function () {
        $this->fake->failWith(ProviderError::because('fake', 'bad request'))
            ->respondWithJson(['value' => 2, 'confidence' => 0.5]);

        $this->gateway->complete(sampleAiRequest());

        expect($this->fake->requests())->toHaveCount(1);
    });

    it('logs an unexpected exception as a failed run and rethrows it', function () {
        $broken = new class implements AiDriver
        {
            public function complete(CompletionRequest $request): CompletionResult
            {
                throw new RuntimeException('the connection broke');
            }

            public function submitBatch(array $requests): string
            {
                throw new RuntimeException('the connection broke');
            }

            public function pollBatch(string $batchId): ?array
            {
                throw new RuntimeException('the connection broke');
            }
        };
        app()->instance($broken::class, $broken);
        config([
            'atlas.ai.providers.broken.driver' => $broken::class,
            'atlas.ai.pricing.broken.*' => ['input_per_million' => 0, 'output_per_million' => 0],
        ]);

        expect(fn () => $this->gateway->complete(sampleAiRequest(provider: 'broken')))
            ->toThrow(RuntimeException::class, 'the connection broke');

        $run = AiRun::query()->sole();
        expect($run->status)->toBe(AiRunStatus::Failed)
            ->and($run->error)->toContain('RuntimeException')->toContain('the connection broke');
    });

    it('fails with no run when the provider is not configured', function () {
        config(['atlas.ai.pricing.nowhere.*' => ['input_per_million' => 0, 'output_per_million' => 0]]);

        expect(fn () => $this->gateway->complete(sampleAiRequest(provider: 'nowhere')))
            ->toThrow(UnknownProvider::class)
            ->and(AiRun::query()->count())->toBe(0);
    });
});

describe('batches', function () {
    it('logs a run per request, sends them as one batch and collects the results', function () {
        $submission = $this->gateway->submitBatch([sampleAiRequest(), sampleAiRequest(['isco_code' => '7412'])]);
        $runs = AiRun::query()->orderBy('id')->get();

        expect($submission->batchId)->toBe('fake-batch-1')
            ->and($submission->runIds)->toBe($runs->pluck('id')->all())
            ->and($runs->pluck('status')->all())->toBe([AiRunStatus::Running, AiRunStatus::Running])
            ->and($runs->pluck('batch_id')->unique()->all())->toBe(['fake-batch-1']);

        $this->fake->respondWithJson(['value' => 3, 'confidence' => 0.6])->respond('not json');

        expect($this->gateway->collectBatch('fake-batch-1'))->toBeTrue();

        $runs = AiRun::query()->orderBy('id')->get();
        expect($runs[0]->status)->toBe(AiRunStatus::Succeeded)
            ->and($runs[0]->parsed_output)->toBe(['value' => 3, 'confidence' => 0.6])
            ->and($runs[1]->status)->toBe(AiRunStatus::Failed)
            ->and($runs[1]->raw_output)->toBe('not json');
    });

    it('waits while the provider is still working', function () {
        $this->gateway->submitBatch([sampleAiRequest()]);
        $this->fake->holdBatches(1)->respondWithJson(['value' => 3, 'confidence' => 0.6]);

        expect($this->gateway->collectBatch('fake-batch-1'))->toBeFalse()
            ->and(AiRun::query()->sole()->status)->toBe(AiRunStatus::Running)
            ->and($this->gateway->collectBatch('fake-batch-1'))->toBeTrue()
            ->and(AiRun::query()->sole()->status)->toBe(AiRunStatus::Succeeded);
    });

    it('fails a run the batch has no result for', function () {
        $this->gateway->submitBatch([sampleAiRequest()]);
        AiRun::factory()->create(['provider' => 'fake', 'prompt_key' => 'test/sample', 'prompt_version' => 1, 'batch_id' => 'fake-batch-1', 'status' => AiRunStatus::Running]);
        $this->fake->respondWithJson(['value' => 3, 'confidence' => 0.6]);

        $this->gateway->collectBatch('fake-batch-1');
        $runs = AiRun::query()->orderBy('id')->get();

        expect($runs[0]->status)->toBe(AiRunStatus::Succeeded)
            ->and($runs[1]->status)->toBe(AiRunStatus::Failed)
            ->and($runs[1]->error)->toContain('no result for this request');
    });

    it('fails every run when the provider refuses the batch', function () {
        $refusing = new class implements AiDriver
        {
            public function complete(CompletionRequest $request): CompletionResult
            {
                throw ProviderError::because('refusing', 'no');
            }

            public function submitBatch(array $requests): string
            {
                throw ProviderError::because('refusing', 'batch too large');
            }

            public function pollBatch(string $batchId): ?array
            {
                return null;
            }
        };
        app()->instance($refusing::class, $refusing);
        config([
            'atlas.ai.providers.refusing.driver' => $refusing::class,
            'atlas.ai.pricing.refusing.*' => ['input_per_million' => 0, 'output_per_million' => 0],
        ]);

        $submission = $this->gateway->submitBatch([sampleAiRequest(provider: 'refusing'), sampleAiRequest(provider: 'refusing')]);

        expect($submission->batchId)->toBeNull()
            ->and($submission->error)->toContain('batch too large')
            ->and(AiRun::query()->where('status', AiRunStatus::Failed)->count())->toBe(2)
            ->and(AiRun::query()->whereNotNull('batch_id')->count())->toBe(0);
    });

    it('refuses a batch that mixes providers or is empty, before any run', function (array $requests) {
        expect(fn () => $this->gateway->submitBatch($requests))->toThrow(InvalidArgumentException::class)
            ->and(AiRun::query()->count())->toBe(0);
    })->with([
        'empty' => [[]],
        'two providers' => fn () => [sampleAiRequest(), sampleAiRequest(provider: 'other')],
    ]);
});

it('closes a run as failed when its answer cannot be stored', function () {
    // Valid for the schema, but PostgreSQL refuses a NUL character inside jsonb.
    $this->fake->respond('{"value": "a\u0000b", "confidence": 0.5}');

    $result = $this->gateway->complete(sampleAiRequest());
    $run = AiRun::query()->sole();

    expect($result->succeeded())->toBeFalse()
        ->and($run->status)->toBe(AiRunStatus::Failed)
        ->and($run->error)->toContain('could not be stored')
        ->and($run->finished_at)->not->toBeNull();
});
