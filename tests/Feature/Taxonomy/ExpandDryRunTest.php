<?php

use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Taxonomy\Actions\ExpandUnitGroup;
use App\Modules\Taxonomy\Jobs\ExpandUnitGroupJob;
use App\Modules\Taxonomy\Models\IscoGroup;
use Illuminate\Support\Facades\Queue;

/*
 * PLAN M10: atlas:taxonomy:expand. A dry run only estimates; a real run checks the whole set against
 * the budget cap before it queues one job per unit group.
 */

beforeEach(function () {
    $this->fake = fakeExpansionAi();
    FieldDefinition::factory()->names()->create();
    IscoGroup::factory()->create(['code' => '7126']);
    IscoGroup::factory()->create(['code' => '7127']);
    Queue::fake();
});

it('prints an estimate and sends or queues nothing on a dry run', function () {
    $this->artisan('atlas:taxonomy:expand', ['--groups' => '7126,7127', '--dry-run' => true])
        ->expectsOutputToContain('2 unit groups: 2 need a model call, 0 reuse an earlier run.')
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    Queue::assertNothingPushed();
    expect(AiRun::query()->count())->toBe(0)
        ->and($this->fake->requests())->toBe([]);
});

it('counts a unit group with an earlier run as reused, not as a call', function () {
    $this->fake->respondWithJson(expansionOutput());
    app(ExpandUnitGroup::class)->handle('7126');

    $this->artisan('atlas:taxonomy:expand', ['--all' => true, '--dry-run' => true])
        ->expectsOutputToContain('2 unit groups: 1 need a model call, 1 reuse an earlier run.')
        ->assertSuccessful();
});

it('queues one job per unit group', function () {
    $this->artisan('atlas:taxonomy:expand', ['--all' => true])
        ->expectsOutputToContain('Queued 2 jobs')
        ->assertSuccessful();

    Queue::assertPushed(ExpandUnitGroupJob::class, 2);
    Queue::assertPushed(ExpandUnitGroupJob::class, fn (ExpandUnitGroupJob $job) => $job->iscoCode === '7127');
});

it('queues nothing when the price is unknown or the set is over the cap', function (array $config, string $message) {
    config($config);

    $this->artisan('atlas:taxonomy:expand', ['--all' => true, '--model' => 'priced-model'])
        ->expectsOutputToContain($message)
        ->assertFailed();

    Queue::assertNothingPushed();
})->with([
    'unknown price' => [['atlas.ai.pricing.fake' => []], 'fake/priced-model'],
    'over the cap' => [['atlas.ai.pricing.fake' => ['priced-model' => ['input_per_million' => 1000, 'output_per_million' => 1000]], 'atlas.ai.max_batch_usd' => 0.01], 'cap'],
]);

it('refuses an unknown unit group and a missing choice of groups', function () {
    $this->artisan('atlas:taxonomy:expand', ['--groups' => '9999'])
        ->expectsOutputToContain('9999 is not an imported ISCO-08 unit group')
        ->assertFailed();

    $this->artisan('atlas:taxonomy:expand')->expectsOutputToContain('either --groups')->assertFailed();
    $this->artisan('atlas:taxonomy:expand', ['--all' => true, '--groups' => '7126'])->assertFailed();

    Queue::assertNothingPushed();
});
