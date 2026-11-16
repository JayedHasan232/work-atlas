<?php

use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;
use Illuminate\Support\Facades\DB;

test('an ISCO code has as many digits as its level and starts with its parent code', function () {
    IscoGroup::factory()->create(['code' => '7411']);

    expect(databaseError(fn () => IscoGroup::query()->create(['code' => '741', 'level' => 4, 'parent_code' => '74', 'title_en' => 'x'])))
        ->toContain('isco_groups_code_check')
        ->and(databaseError(fn () => IscoGroup::query()->create(['code' => '7499', 'level' => 4, 'parent_code' => '73', 'title_en' => 'x'])))
        ->toContain('isco_groups_parent_check')
        ->and(databaseError(fn () => IscoGroup::query()->create(['code' => '8', 'level' => 1, 'parent_code' => '7', 'title_en' => 'x'])))
        ->toContain('isco_groups_parent_check')
        ->and(databaseError(fn () => IscoGroup::query()->create(['code' => '7412', 'level' => 4, 'parent_code' => '741', 'title_en' => 'x'])))
        ->toBe('(accepted)');
});

test('an occupation needs an existing ISCO group and a URL-safe slug', function () {
    expect(databaseError(fn () => Occupation::factory()->create(['isco_code' => '9999'])))
        ->toContain('occupations_isco_code_foreign')
        ->and(databaseError(fn () => Occupation::factory()->create(['slug' => 'Electrician Helper'])))
        ->toContain('occupations_slug_check');
});

test('only merged occupations point to another occupation, and never to themselves', function () {
    $target = Occupation::factory()->create();
    $occupation = Occupation::factory()->create();

    expect(databaseError(fn () => DB::update('UPDATE occupations SET merged_into_id = ? WHERE id = ?', [$target->id, $occupation->id])))
        ->toContain('occupations_merged_into_check')
        ->and(databaseError(fn () => DB::update("UPDATE occupations SET publish_state = 'merged' WHERE id = ?", [$occupation->id])))
        ->toContain('occupations_merged_into_check')
        ->and(databaseError(fn () => DB::update("UPDATE occupations SET publish_state = 'merged', merged_into_id = id WHERE id = ?", [$occupation->id])))
        ->toContain('occupations_merged_into_check')
        ->and(databaseError(fn () => DB::update("UPDATE occupations SET publish_state = 'merged', merged_into_id = ?, indexable = true WHERE id = ?", [$target->id, $occupation->id])))
        ->toContain('occupations_merged_not_indexable_check');
});

test('an occupation has one primary name per locale, and regional names name their region', function () {
    $occupation = Occupation::factory()->create();
    OccupationName::factory()->primary()->bangla('বিদ্যুৎ মিস্ত্রি')->create(['occupation_id' => $occupation->id]);

    expect(databaseError(fn () => OccupationName::factory()->primary()->bangla('ইলেকট্রিশিয়ান')->create(['occupation_id' => $occupation->id])))
        ->toContain('occupation_names_one_primary_per_locale')
        ->and(databaseError(fn () => OccupationName::factory()->primary()->create(['occupation_id' => $occupation->id, 'locale' => 'en'])))
        ->toBe('(accepted)')
        ->and(databaseError(fn () => OccupationName::factory()->create(['occupation_id' => $occupation->id, 'type' => 'regional', 'region' => null])))
        ->toContain('occupation_names_region_check')
        ->and(databaseError(fn () => OccupationName::factory()->bangla('বিদ্যুৎ মিস্ত্রি')->create(['occupation_id' => $occupation->id])))
        ->toContain('occupation_names_occupation_id_locale_normalized_name_unique');
});
