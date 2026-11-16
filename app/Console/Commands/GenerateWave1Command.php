<?php

namespace App\Console\Commands;

use App\Modules\AiPipeline\Actions\QueueWave1;
use App\Modules\AiPipeline\Exceptions\BudgetExceeded;
use App\Modules\AiPipeline\Exceptions\CrossCheckNeedsTwoModels;
use App\Modules\AiPipeline\Exceptions\InvalidPrompt;
use App\Modules\Taxonomy\Exceptions\UnknownOccupation;
use App\Modules\Taxonomy\Queries\ListedOccupationIds;
use Illuminate\Console\Command;

/**
 * PLAN M12: Wave 1 values for listed occupations, the same prompt on two models, one queued job per
 * occupation. A paid run needs the maintainer's go-ahead and a dry-run estimate first (CLAUDE.md).
 */
final class GenerateWave1Command extends Command
{
    protected $signature = 'atlas:wave1:generate
        {--occupations= : occupation slugs, comma-separated}
        {--all : every listed and published occupation}
        {--dry-run : only print the cost estimate; nothing is sent or queued}
        {--force : make new calls even when earlier runs with the same input succeeded}';

    protected $description = 'Generate Wave 1 field values with a two-model cross-check (queued)';

    public function handle(ListedOccupationIds $listed, QueueWave1 $queue): int
    {
        $slugs = $this->option('occupations');
        $all = (bool) $this->option('all');

        if ($all === (is_string($slugs) && $slugs !== '')) {
            $this->components->error('Give either --occupations=<slugs> or --all.');

            return self::FAILURE;
        }

        try {
            $ids = $listed->handle($all ? null : array_values(array_filter(array_map(trim(...), explode(',', (string) $slugs)))));

            if ($ids === []) {
                $this->components->error('There are no listed occupations; review the list first (M11).');

                return self::FAILURE;
            }

            $plan = $queue->handle($ids, (bool) $this->option('force'), (bool) $this->option('dry-run'));
        } catch (UnknownOccupation|BudgetExceeded|InvalidPrompt|CrossCheckNeedsTwoModels $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $estimate = $plan->estimate;
        $this->components->info(sprintf('%d occupations, 2 models: %d calls needed, %d reuse an earlier run.', count($plan->occupationIds), $estimate->requests, $plan->reused));
        $this->components->info(sprintf('About %d input and %d output tokens.', $estimate->inputTokens, $estimate->outputTokens));
        $this->components->info(sprintf(
            'Cost: %s (cap per batch: $%s).',
            $estimate->usd === null ? 'unknown, no price for '.implode(', ', $estimate->unpriced) : sprintf('$%.4f', $estimate->usd),
            (string) config('atlas.ai.max_batch_usd'),
        ));
        $this->components->info($plan->queued ? sprintf('Queued %d jobs.', count($plan->occupationIds)) : 'Dry run: nothing was sent or queued.');

        return self::SUCCESS;
    }
}
