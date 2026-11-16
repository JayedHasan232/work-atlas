<?php

namespace App\Console\Commands;

use App\Modules\AiPipeline\Exceptions\BudgetExceeded;
use App\Modules\AiPipeline\Exceptions\InvalidPrompt;
use App\Modules\Taxonomy\Actions\QueueUnitGroupExpansion;
use App\Modules\Taxonomy\Exceptions\UnknownUnitGroup;
use App\Modules\Taxonomy\Queries\UnitGroupCodes;
use Illuminate\Console\Command;

/**
 * PLAN M10: ask the AI which occupations belong to each ISCO-08 unit group, one queued job per group.
 * A paid run needs the maintainer's go-ahead and a dry-run estimate first (CLAUDE.md).
 */
final class ExpandTaxonomyCommand extends Command
{
    protected $signature = 'atlas:taxonomy:expand
        {--groups= : unit group codes, comma-separated (for example 7411,7412)}
        {--all : every imported unit group}
        {--dry-run : only print the cost estimate; nothing is sent or queued}
        {--force : make a new call even when an earlier run with the same input succeeded}
        {--provider= : AI provider (default: config atlas.ai.tasks.taxonomy_expand)}
        {--model= : AI model (default: config atlas.ai.tasks.taxonomy_expand)}';

    protected $description = 'Propose candidate occupations for ISCO-08 unit groups with the AI (queued)';

    public function handle(UnitGroupCodes $unitGroups, QueueUnitGroupExpansion $queue): int
    {
        $groups = $this->option('groups');
        $all = (bool) $this->option('all');

        if ($all === (is_string($groups) && $groups !== '')) {
            $this->components->error('Give either --groups=<codes> or --all.');

            return self::FAILURE;
        }

        try {
            $codes = $unitGroups->handle($all ? null : explode(',', (string) $groups));

            if ($codes === []) {
                $this->components->error('There are no unit groups; run atlas:taxonomy:import first.');

                return self::FAILURE;
            }

            $plan = $queue->handle(
                $codes,
                $this->stringOption('provider'),
                $this->stringOption('model'),
                (bool) $this->option('force'),
                (bool) $this->option('dry-run'),
            );
        } catch (UnknownUnitGroup|BudgetExceeded|InvalidPrompt $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $estimate = $plan->estimate;
        $this->components->info(sprintf('%d unit groups: %d need a model call, %d reuse an earlier run.', count($plan->groups), $estimate->requests, $plan->reused));
        $this->components->info(sprintf('About %d input and %d output tokens.', $estimate->inputTokens, $estimate->outputTokens));
        $this->components->info(sprintf(
            'Cost: %s (cap per batch: $%s).',
            $estimate->usd === null ? 'unknown, no price for '.implode(', ', $estimate->unpriced) : sprintf('$%.4f', $estimate->usd),
            (string) config('atlas.ai.max_batch_usd'),
        ));
        $this->components->info($plan->queued ? sprintf('Queued %d jobs.', count($plan->groups)) : 'Dry run: nothing was sent or queued.');

        return self::SUCCESS;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
