<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Data\FieldSyncResult;
use App\Modules\Knowledge\Exceptions\DefinitionChangeNeedsNewVersion;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use BackedEnum;
use Illuminate\Support\Facades\DB;

/**
 * Makes `field_definitions` match the field files, in one transaction. A new key or version is
 * created; a changed definition is updated; an unchanged one is left alone, so a second run changes
 * nothing. Once a definition has values, its meaning (type, options, anchors, wave) is fixed: the sync
 * refuses the change and asks for a new version. Labels, help, group and flags can change in place.
 * Definitions no file lists are kept and reported, never deleted: values may use them.
 */
final class SyncFieldDefinitions
{
    /** The columns that carry a definition's meaning. */
    public const array MEANING = ['type', 'options', 'anchors', 'wave'];

    public function __construct(
        private readonly ReadFieldFiles $readFieldFiles,
    ) {}

    public function handle(string $directory): FieldSyncResult
    {
        $definitions = $this->readFieldFiles->handle($directory);

        return DB::transaction(function () use ($definitions): FieldSyncResult {
            $created = $updated = [];
            $unchanged = 0;
            $listed = [];

            foreach ($definitions as $data) {
                $id = "{$data->key} v{$data->version}";
                $listed[] = $id;
                $definition = FieldDefinition::query()
                    ->where('key', $data->key)
                    ->where('version', $data->version)
                    ->lockForUpdate()
                    ->first();

                if ($definition === null) {
                    FieldDefinition::query()->create(['key' => $data->key, 'version' => $data->version, ...$data->attributes()]);
                    $created[] = $id;

                    continue;
                }

                $changed = $this->changedColumns($definition, $data);

                if ($changed === []) {
                    $unchanged++;

                    continue;
                }

                $meaning = array_values(array_intersect(self::MEANING, $changed));

                if ($meaning !== [] && FieldValue::query()->where('field_definition_id', $definition->id)->exists()) {
                    throw DefinitionChangeNeedsNewVersion::for($data->key, $data->version, $meaning);
                }

                $definition->fill($data->attributes())->save();
                $updated[] = $id;
            }

            $stored = FieldDefinition::query()
                ->orderBy('key')->orderBy('version')
                ->get(['key', 'version'])
                ->map(fn (FieldDefinition $definition): string => "{$definition->key} v{$definition->version}")
                ->all();
            $notInFiles = array_values(array_diff($stored, $listed));

            return new FieldSyncResult($created, $updated, $unchanged, $notInFiles);
        });
    }

    /**
     * Compares by value: jsonb keeps object keys in its own order, so a plain dirty check would see a
     * change on every run.
     *
     * @return list<string>
     */
    private function changedColumns(FieldDefinition $definition, FieldDefinitionData $data): array
    {
        $changed = [];

        foreach ($data->attributes() as $column => $value) {
            if ($this->normalize($definition->getAttribute($column)) !== $this->normalize($value)) {
                $changed[] = $column;
            }
        }

        return $changed;
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (! is_array($value)) {
            return $value;
        }

        $value = array_map($this->normalize(...), $value);

        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }
}
