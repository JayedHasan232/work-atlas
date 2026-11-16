<?php

namespace App\Console\Commands;

use App\Modules\Knowledge\Data\SourceInput;
use App\Modules\Knowledge\Exceptions\InvalidSource;
use App\Modules\Taxonomy\Actions\ImportIscoGroups;
use App\Modules\Taxonomy\Exceptions\InvalidTaxonomyFile;
use Illuminate\Console\Command;
use JsonException;

/**
 * Imports the ISCO-08 groups from `data/taxonomy/isco08.csv`, with the source described in
 * `data/taxonomy/isco08.source.json` (url, publisher, title, license, accessed_on, archive_url).
 * Safe to run again: an unchanged file changes nothing.
 */
final class ImportTaxonomyCommand extends Command
{
    protected $signature = 'atlas:taxonomy:import
        {--file=data/taxonomy/isco08.csv : The ISCO-08 CSV file (code,title_en)}
        {--source=data/taxonomy/isco08.source.json : The JSON file that describes where the CSV comes from}';

    protected $description = 'Import the ISCO-08 groups and record their source';

    public function handle(ImportIscoGroups $import): int
    {
        $file = $this->path((string) $this->option('file'));
        $sourceFile = $this->path((string) $this->option('source'));

        try {
            $result = $import->handle($file, $this->source($sourceFile));
        } catch (InvalidTaxonomyFile|InvalidSource $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            '%d created, %d updated, %d unchanged (source #%d).',
            $result->created,
            $result->updated,
            $result->unchanged,
            $result->sourceId,
        ));

        $this->table(
            ['Level', 'In the file', 'ISCO-08 has'],
            array_map(
                fn (int $level): array => [$level, $result->groupsPerLevel[$level], ImportIscoGroups::ISCO08_GROUP_COUNTS[$level]],
                array_keys(ImportIscoGroups::ISCO08_GROUP_COUNTS),
            ),
        );

        if ($result->groupsPerLevel !== ImportIscoGroups::ISCO08_GROUP_COUNTS) {
            $this->components->warn('The file is not the complete ISCO-08 structure.');
        }

        return self::SUCCESS;
    }

    private function path(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    private function source(string $path): SourceInput
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw InvalidSource::because("the source file {$path} does not exist");
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 4, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidSource::because("{$path} is not valid JSON");
        }

        if (! is_array($data)) {
            throw InvalidSource::because("{$path} must hold a JSON object");
        }

        foreach (['url', 'publisher', 'title', 'license', 'accessed_on'] as $key) {
            if (! is_string($data[$key] ?? null) || trim($data[$key]) === '') {
                throw InvalidSource::because("{$path} needs \"{$key}\"");
            }
        }

        $archiveUrl = $data['archive_url'] ?? null;

        return new SourceInput(
            url: $data['url'],
            accessedOn: $data['accessed_on'],
            publisher: $data['publisher'],
            title: $data['title'],
            license: $data['license'],
            archiveUrl: is_string($archiveUrl) && $archiveUrl !== '' ? $archiveUrl : null,
        );
    }
}
