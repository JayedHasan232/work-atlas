<?php

use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use Illuminate\Support\Facades\DB;

test('a field definition key and version are unique, and a new version is a new row', function () {
    $definition = FieldDefinition::factory()->create(['key' => 'physical_workload']);

    expect(databaseError(fn () => FieldDefinition::factory()->create(['key' => 'physical_workload'])))
        ->toContain('field_definitions_key_version_unique')
        ->and(databaseError(fn () => FieldDefinition::factory()->create(['key' => 'physical_workload', 'version' => 2])))
        ->toBe('(accepted)')
        ->and($definition->refresh()->version)->toBe(1);
});

test('scales need anchors in both languages, and choice fields need options', function () {
    expect(databaseError(fn () => FieldDefinition::factory()->create(['anchors' => ['en' => ['1' => 'x']]])))
        ->toContain('field_definitions_anchors_check')
        ->and(databaseError(fn () => FieldDefinition::factory()->enum()->create(['options' => null])))
        ->toContain('field_definitions_options_check')
        ->and(databaseError(fn () => FieldDefinition::factory()->text()->create(['options' => ['a']])))
        ->toContain('field_definitions_options_check')
        // The enum cast stops an unknown type in PHP; the check stops it for raw SQL too.
        ->and(databaseError(fn () => DB::update("UPDATE field_definitions SET type = 'number' WHERE id = ?", [FieldDefinition::factory()->text()->create()->id])))
        ->toContain('field_definitions_type_check');
});

test('there is one value slot per occupation, field, locale and region, empty ones included', function () {
    $value = FieldValue::factory()->create();
    $slot = ['occupation_id' => $value->occupation_id, 'field_definition_id' => $value->field_definition_id];

    expect(databaseError(fn () => FieldValue::factory()->create($slot)))
        ->toContain('field_values_slot_unique')
        ->and(databaseError(fn () => FieldValue::factory()->create([...$slot, 'locale' => 'bn'])))
        ->toBe('(accepted)')
        ->and(databaseError(fn () => FieldValue::factory()->create([...$slot, 'locale' => 'bn'])))
        ->toContain('field_values_slot_unique')
        ->and(databaseError(fn () => FieldValue::factory()->create([...$slot, 'region' => 'khulna'])))
        ->toBe('(accepted)');
});

test('a value status is one of the five statuses', function () {
    $value = FieldValue::factory()->create();

    expect(databaseError(fn () => DB::update("UPDATE field_values SET status = 'approved' WHERE id = ?", [$value->id])))
        ->toContain('field_values_status_check');
});
