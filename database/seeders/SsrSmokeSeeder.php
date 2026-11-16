<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Actions\SyncFieldDefinitions;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Queries\FieldDefinitionByKey;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;
use Illuminate\Database\Seeder;

/**
 * One placeholder occupation for the SSR smoke test in CI (`/occupations/ssr-smoke`), with names
 * only: no field values, so nothing here looks like a fact about real work.
 */
final class SsrSmokeSeeder extends Seeder
{
    public function run(SyncFieldDefinitions $fields, FieldDefinitionByKey $definitions, RecordRevision $record): void
    {
        $fields->handle(base_path('data/fields'));

        IscoGroup::factory()->create(['code' => '7126']);
        $occupation = Occupation::factory()->create([
            'slug' => 'ssr-smoke',
            'isco_code' => '7126',
            'publish_state' => PublishState::Listed,
        ]);
        $actor = Actor::user(User::factory()->role(Role::Maintainer)->create());
        $names = $definitions->handle('names') ?? throw new \RuntimeException('The names field is missing.');

        foreach (['bn' => 'পরীক্ষার পেশা', 'en' => 'Smoke test occupation'] as $locale => $name) {
            $record->handle(new RevisionInput(
                occupationId: $occupation->id,
                fieldDefinitionId: $names,
                value: [['name' => $name, 'type' => 'primary']],
                actor: $actor,
                locale: $locale,
                comment: 'SSR smoke placeholder',
            ));
        }
    }
}
