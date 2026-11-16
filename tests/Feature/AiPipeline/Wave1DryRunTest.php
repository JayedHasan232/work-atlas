<?php

use App\Modules\AiPipeline\Jobs\GenerateWave1Job;
use App\Modules\AiPipeline\Models\AiRun;
use Illuminate\Support\Facades\Queue;

/*
 * `atlas:wave1:generate` (PLAN M12): a dry run prints the estimate for both models and sends or
 * queues nothing; a real run checks the cap first and queues one job per occupation.
 */

beforeEach(function () {
    $this->fake = wave1Setup();
    $this->occupation = listedOccupation();
    Queue::fake();
});

it('prints the estimate for two calls per occupation and sends nothing', function () {
    $this->artisan('atlas:wave1:generate', ['--all' => true, '--dry-run' => true])
        ->expectsOutputToContain('1 occupations, 2 models: 2 calls needed, 0 reuse an earlier run.')
        ->expectsOutputToContain('Dry run: nothing was sent or queued.')
        ->assertSuccessful();

    expect($this->fake->requests())->toBe([])
        ->and(AiRun::query()->where('prompt_key', 'wave1/fields')->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('queues one job per listed occupation within the cap', function () {
    $this->artisan('atlas:wave1:generate', ['--occupations' => $this->occupation->slug])
        ->expectsOutputToContain('Queued 1 jobs.')
        ->assertSuccessful();

    Queue::assertPushed(GenerateWave1Job::class, fn (GenerateWave1Job $job) => $job->occupationId === $this->occupation->id);
});

it('refuses a run above the cap before anything is queued', function () {
    config(['atlas.ai.pricing.fake' => ['*' => ['input_per_million' => 1000, 'output_per_million' => 1000]], 'atlas.ai.max_batch_usd' => 0.0001]);

    $this->artisan('atlas:wave1:generate', ['--all' => true])->assertFailed();

    Queue::assertNothingPushed();
});

it('refuses an occupation that is not listed', function () {
    $this->occupation->update(['publish_state' => 'candidate']);

    $this->artisan('atlas:wave1:generate', ['--occupations' => $this->occupation->slug])
        ->expectsOutputToContain('Not a listed or published occupation')
        ->assertFailed();
});
