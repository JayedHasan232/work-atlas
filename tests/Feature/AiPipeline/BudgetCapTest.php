<?php

use App\Modules\AiPipeline\AiGateway;
use App\Modules\AiPipeline\Exceptions\BudgetExceeded;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\AiPipeline\PromptRegistry;

/*
 * A paid run needs the maintainer's go-ahead (CLAUDE.md, "AI data generation"): the gateway refuses
 * any call or batch whose estimated cost is above atlas.ai.max_batch_usd, or whose price is unknown,
 * before anything is sent. The prices here are test numbers, not any provider's real prices.
 */

beforeEach(function () {
    $this->fake = fakeAi();
    $this->gateway = app(AiGateway::class);
    config(['atlas.ai.pricing.fake.priced-model' => ['input_per_million' => 1000, 'output_per_million' => 2000]]);
});

it('estimates the cost from the prompt length and the maximum output', function () {
    $estimate = $this->gateway->estimate([sampleAiRequest(model: 'priced-model'), sampleAiRequest(model: 'priced-model')]);
    $chars = mb_strlen(app(PromptRegistry::class)->get('test/sample', 1)->render(['occupation' => 'Electrician', 'isco_code' => '7411']));

    expect($estimate->requests)->toBe(2)
        ->and($estimate->inputTokens)->toBe(2 * $chars)
        ->and($estimate->outputTokens)->toBe(400)
        ->and($estimate->usd)->toBe(round((2 * $chars * 1000 + 400 * 2000) / 1_000_000, 6))
        ->and($estimate->unpriced)->toBe([])
        ->and(AiRun::query()->count())->toBe(0)
        ->and($this->fake->requests())->toBe([]);
});

it('refuses a call above the cap without a run or a provider call', function () {
    config(['atlas.ai.max_batch_usd' => 0.01]);

    expect(fn () => $this->gateway->complete(sampleAiRequest(model: 'priced-model')))
        ->toThrow(BudgetExceeded::class, 'above the cap of 0.010000 USD')
        ->and(AiRun::query()->count())->toBe(0)
        ->and($this->fake->requests())->toBe([]);
});

it('refuses a batch whose total is above the cap, even when each request is below it', function () {
    $single = $this->gateway->estimate([sampleAiRequest(model: 'priced-model')])->usd;
    config(['atlas.ai.max_batch_usd' => $single * 1.5]);

    expect(fn () => $this->gateway->submitBatch([sampleAiRequest(model: 'priced-model'), sampleAiRequest(model: 'priced-model')]))
        ->toThrow(BudgetExceeded::class)
        ->and(AiRun::query()->count())->toBe(0)
        ->and($this->fake->requests())->toBe([]);
});

it('allows a call within the cap', function () {
    $estimate = $this->gateway->estimate([sampleAiRequest(model: 'priced-model')])->usd;
    config(['atlas.ai.max_batch_usd' => $estimate]);
    $this->fake->respondWithJson(['value' => 1, 'confidence' => 0.5], inputTokens: 1000, outputTokens: 500);

    $result = $this->gateway->complete(sampleAiRequest(model: 'priced-model'));

    expect($result->succeeded())->toBeTrue()
        ->and(AiRun::query()->sole()->cost_usd)->toBe('2.000000');
});

it('refuses a model without a price, whatever the cap', function () {
    config(['atlas.ai.max_batch_usd' => 1_000_000, 'atlas.ai.pricing.fake' => []]);

    expect(fn () => $this->gateway->complete(sampleAiRequest(model: 'unpriced')))
        ->toThrow(BudgetExceeded::class, 'No price in atlas.ai.pricing for fake/unpriced')
        ->and(AiRun::query()->count())->toBe(0)
        ->and($this->gateway->estimate([sampleAiRequest(model: 'unpriced')])->usd)->toBeNull();
});

it('has a cap of zero unless the maintainer sets one', function () {
    expect(config('atlas.ai.max_batch_usd'))->toBe(0.0);
});
