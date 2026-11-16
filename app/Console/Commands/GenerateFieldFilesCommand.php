<?php

namespace App\Console\Commands;

use App\Modules\Knowledge\Actions\BuildFieldFiles;
use App\Modules\Knowledge\Exceptions\InvalidFieldFile;
use Illuminate\Console\Command;

/**
 * Writes the JSON schemas (`data/schemas/wave<N>/fields.v<M>.json`) and `docs/architecture/fields.md`
 * from `data/fields`. A schema file, once written, is never changed (prompts and schemas are
 * immutable): a different schema needs a new wave file version. `--check` only reports drift.
 */
final class GenerateFieldFilesCommand extends Command
{
    protected $signature = 'atlas:fields:generate
        {--path=data/fields : The folder with the wave<N>.v<M>.yaml files}
        {--check : Fail if a generated file is missing or out of date, without writing}';

    protected $description = 'Generate the field schemas and the field table from data/fields';

    public function handle(BuildFieldFiles $build): int
    {
        $path = (string) $this->option('path');

        try {
            $files = $build->handle(str_starts_with($path, '/') ? $path : base_path($path));
        } catch (InvalidFieldFile $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $failed = $wrote = false;

        foreach ($files as $relative => $contents) {
            $target = base_path($relative);
            $current = is_file($target) ? file_get_contents($target) : null;

            if ($current === $contents) {
                continue;
            }

            $isSchema = str_starts_with($relative, 'data/schemas/');

            if ($this->option('check') || ($isSchema && $current !== null)) {
                $this->components->error($isSchema && $current !== null
                    ? "{$relative} would change, but schema files are immutable. Add a new wave file version."
                    : "{$relative} is ".($current === null ? 'missing' : 'out of date').'. Run atlas:fields:generate.');
                $failed = true;

                continue;
            }

            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }

            file_put_contents($target, $contents);
            $this->components->info("Wrote {$relative}.");
            $wrote = true;
        }

        if (! $failed && ! $wrote) {
            $this->components->info('Generated files are up to date.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
