<?php

namespace App\Console\Commands;

use App\Modules\Taxonomy\Actions\DetectDuplicates;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * PLAN M11: flag possible duplicate occupations for the list review in /admin.
 */
final class DetectDuplicatesCommand extends Command
{
    protected $signature = 'atlas:taxonomy:duplicates
        {--threshold= : trigram similarity from above 0 to 1 (default: config atlas.taxonomy.duplicate_threshold)}';

    protected $description = 'Flag possible duplicate occupations (same ISCO-08 minor group, similar names); safe to run again';

    public function handle(DetectDuplicates $detect): int
    {
        $threshold = $this->option('threshold');

        if ($threshold !== null && ! is_numeric($threshold)) {
            $this->components->error('--threshold must be a number from above 0 to 1, for example 0.5.');

            return self::FAILURE;
        }

        try {
            $created = $detect->handle(is_numeric($threshold) ? (float) $threshold : null);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('%d new possible duplicate pairs.', $created));

        return self::SUCCESS;
    }
}
