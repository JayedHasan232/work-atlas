<?php

namespace App\Console\Commands;

use App\Modules\Knowledge\Actions\SyncFieldDefinitions;
use App\Modules\Knowledge\Exceptions\DefinitionChangeNeedsNewVersion;
use App\Modules\Knowledge\Exceptions\InvalidFieldFile;
use Illuminate\Console\Command;

/**
 * Makes the field definitions in the database match `data/fields/*.yaml`. Safe to run again.
 */
final class SyncFieldDefinitionsCommand extends Command
{
    protected $signature = 'atlas:fields:sync {--path=data/fields : The folder with the wave<N>.v<M>.yaml files}';

    protected $description = 'Sync the field definitions from data/fields';

    public function handle(SyncFieldDefinitions $sync): int
    {
        $path = (string) $this->option('path');

        try {
            $result = $sync->handle(str_starts_with($path, '/') ? $path : base_path($path));
        } catch (InvalidFieldFile|DefinitionChangeNeedsNewVersion $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('%d created, %d updated, %d unchanged.', count($result->created), count($result->updated), $result->unchanged));

        foreach (['Created' => $result->created, 'Updated' => $result->updated] as $label => $ids) {
            if ($ids !== []) {
                $this->components->twoColumnDetail($label, implode(', ', $ids));
            }
        }

        if ($result->notInFiles !== []) {
            $this->components->warn('Kept, but no file lists them: '.implode(', ', $result->notInFiles));
        }

        return self::SUCCESS;
    }
}
