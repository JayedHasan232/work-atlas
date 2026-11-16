<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Data\FieldDefinitionData;

/**
 * The files generated from the field files: one JSON schema per wave (the latest version of each of
 * its fields, numbered after the newest wave file) and the field table. Returns path => contents,
 * paths relative to the project root.
 */
final class BuildFieldFiles
{
    public const string DOCS_PATH = 'docs/architecture/fields.md';

    public function __construct(
        private readonly ReadFieldFiles $readFieldFiles,
        private readonly FieldSchemaBuilder $schemaBuilder,
        private readonly RenderFieldTable $renderFieldTable,
    ) {}

    /**
     * @return array<string, string>
     */
    public function handle(string $directory): array
    {
        $definitions = $this->readFieldFiles->handle($directory);
        $files = [];
        $latest = [];
        $fileVersions = [];

        foreach ($definitions as $definition) {
            $current = $latest[$definition->wave][$definition->key] ?? null;

            if ($current === null || $definition->version > $current->version) {
                $latest[$definition->wave][$definition->key] = $definition;
            }

            preg_match('/\.v(\d+)\.yaml$/', $definition->file, $match);
            $fileVersions[$definition->wave] = max($fileVersions[$definition->wave] ?? 1, (int) ($match[1] ?? 1));
        }

        ksort($latest);

        foreach ($latest as $wave => $fields) {
            /** @var list<FieldDefinitionData> $fields */
            $fields = array_values($fields);
            $version = $fileVersions[$wave];
            $schema = $this->schemaBuilder->handle($fields, $version);
            $files["data/schemas/wave{$wave}/fields.v{$version}.json"] = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
        }

        $files[self::DOCS_PATH] = $this->renderFieldTable->handle($definitions);

        return $files;
    }
}
