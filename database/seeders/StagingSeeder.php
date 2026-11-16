<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Actions\SyncFieldDefinitions;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Queries\FieldDefinitionByKey;
use App\Modules\Knowledge\Queries\PublicFieldDefinitions;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fake data for the staging server (PLAN M22, A12: "fake data only"). Every occupation is called
 * "Example occupation N (staging)" so nothing here can pass for a fact about real work, and the
 * Wave 1 values are fixed picks from each field's own options. It runs without Faker or the model
 * factories, because staging installs without dev dependencies. Safe to run again: an example
 * occupation that exists already is left alone. It refuses to run in production.
 */
final class StagingSeeder extends Seeder
{
    public const string SLUG_PREFIX = 'staging-example-';

    /** ISCO-08 unit group codes, so browsing by group works; the occupations in them are not real. */
    private const array GROUPS = ['7411', '7126', '9112', '5120', '8322', '6111', '5311', '7531', '2341', '9312', '3221', '5223'];

    public function run(
        SyncFieldDefinitions $fields,
        PublicFieldDefinitions $definitions,
        FieldDefinitionByKey $definitionIds,
        RecordRevision $record,
        ChangeStatus $status,
    ): void {
        if (App::isProduction()) {
            throw new RuntimeException('The staging seeder never runs in production.');
        }

        $fields->handle(base_path('data/fields'));
        $this->groups();

        $seeder = User::query()->firstOrCreate(
            ['email' => 'staging-seeder@example.test'],
            ['name' => 'Staging seeder', 'password' => Hash::make(Str::random(64))],
        );
        $seeder->forceFill(['role' => Role::Maintainer])->save();
        $actor = Actor::user($seeder);
        $names = $definitionIds->handle('names') ?? throw new RuntimeException('The names field is missing.');
        $wave1 = array_filter($definitions->handle([1]), fn (FieldDefinitionData $field): bool => $field->type !== FieldType::NameList);

        foreach (self::GROUPS as $index => $group) {
            $number = $index + 1;
            $slug = self::SLUG_PREFIX.$number;

            if (Occupation::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $occupation = Occupation::query()->create([
                'slug' => $slug,
                'isco_code' => $group,
                'publish_state' => PublishState::Listed,
            ]);

            foreach (['bn' => "নমুনা পেশা {$this->bangla($number)} (স্টেজিং)", 'en' => "Example occupation {$number} (staging)"] as $locale => $name) {
                $record->handle(new RevisionInput(
                    occupationId: $occupation->id,
                    fieldDefinitionId: $names,
                    value: [['name' => $name, 'type' => 'primary']],
                    actor: $actor,
                    locale: $locale,
                    comment: 'Staging fake data',
                ));
            }

            foreach ($wave1 as $field) {
                $fieldId = $definitionIds->handle($field->key, $field->version);
                $value = $this->fakeValue($field, "{$slug}:{$field->key}");

                if ($fieldId === null || $value === null) {
                    continue;
                }

                $revision = $record->handle(new RevisionInput(
                    occupationId: $occupation->id,
                    fieldDefinitionId: $fieldId,
                    value: $value,
                    actor: $actor,
                    comment: 'Staging fake data',
                ));

                // A mix of statuses (a maintainer's value starts as community_supported).
                $target = [FieldStatus::Verified, FieldStatus::Disputed, null][$this->pick("{$slug}:{$field->key}:status", 3)];

                if ($target !== null) {
                    $status->handle($revision->field_value_id, $target, $actor);
                }
            }
        }
    }

    /**
     * The groups and their parents. Groups imported from ISCO-08 (`atlas:taxonomy:import`) are kept;
     * missing ones get a placeholder title, since the ISCO file waits on O9.
     */
    private function groups(): void
    {
        foreach (self::GROUPS as $group) {
            foreach ([1, 2, 3, 4] as $level) {
                $code = substr($group, 0, $level);

                IscoGroup::query()->firstOrCreate(['code' => $code], [
                    'level' => $level,
                    'parent_code' => $level === 1 ? null : substr($group, 0, $level - 1),
                    'title_en' => "Example group {$code} (staging)",
                    'title_bn' => "নমুনা দল {$this->bangla($code)} (স্টেজিং)",
                ]);
            }
        }
    }

    private function fakeValue(FieldDefinitionData $field, string $seed): mixed
    {
        $options = $field->options ?? [];

        return match ($field->type) {
            FieldType::Scale1To5 => $this->pick($seed, 5) + 1,
            FieldType::Boolean => $this->pick($seed, 2) === 1,
            FieldType::Enum, FieldType::Band => $options === [] ? null : $options[$this->pick($seed, count($options))],
            FieldType::MultiEnum => $options === [] ? null : (array_values(array_filter($options, fn (string $option): bool => $this->pick("{$seed}:{$option}", 2) === 1)) ?: [$options[0]]),
            FieldType::Text => 'Staging placeholder text. Not a fact about any real work.',
            FieldType::NameList => null,
        };
    }

    /**
     * A fixed pick from 0 to $count - 1: the same seed always gives the same fake value, so staging
     * looks the same after every reseed.
     */
    private function pick(string $seed, int $count): int
    {
        return crc32($seed) % $count;
    }

    private function bangla(int|string $number): string
    {
        return strtr((string) $number, ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯']);
    }
}
