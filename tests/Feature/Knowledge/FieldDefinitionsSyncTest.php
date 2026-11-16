<?php

use App\Modules\Knowledge\Actions\ReadFieldFiles;
use App\Modules\Knowledge\Actions\SyncFieldDefinitions;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Exceptions\InvalidFieldFile;
use App\Modules\Knowledge\Models\FieldDefinition;

test('after a sync the database definitions match the field files', function () {
    $result = app(SyncFieldDefinitions::class)->handle(base_path('data/fields'));
    $files = app(ReadFieldFiles::class)->handle(base_path('data/fields'));

    expect($result->created)->toHaveCount(12)
        ->and(FieldDefinition::query()->count())->toBe(count($files));

    foreach ($files as $data) {
        $definition = FieldDefinition::query()->where('key', $data->key)->where('version', $data->version)->sole();

        expect($definition->wave)->toBe($data->wave)
            ->and($definition->group)->toBe($data->group)
            ->and($definition->type)->toBe($data->type)
            ->and($definition->options)->toBe($data->options)
            ->and($definition->anchors)->toEqual($data->anchors)
            ->and($definition->label)->toEqual($data->label)
            ->and($definition->help)->toEqual($data->help)
            ->and($definition->option_labels)->toEqual($data->optionLabels)
            ->and($definition->sensitive)->toBe($data->sensitive)
            ->and($definition->requires_source)->toBe($data->requiresSource);
    }
});

test('the Wave 1 file has the eleven fields of A5, none sensitive and none needing a source', function () {
    $wave1 = array_values(array_filter(app(ReadFieldFiles::class)->handle(base_path('data/fields')), fn ($data) => $data->wave === 1));

    expect(array_map(fn ($data) => $data->key, $wave1))->toBe([
        'education_required', 'formal_training', 'physical_workload', 'mental_workload', 'work_setting',
        'work_time', 'seasonal', 'time_to_learn', 'time_to_first_income', 'part_time_possible', 'available_in',
    ]);

    foreach ($wave1 as $data) {
        expect($data->sensitive)->toBeFalse()
            ->and($data->requiresSource)->toBeFalse();
    }
});

test('wave 0 holds only the names field, a name list that is neither sensitive nor needs a source (D44)', function () {
    $wave0 = array_values(array_filter(app(ReadFieldFiles::class)->handle(base_path('data/fields')), fn ($data) => $data->wave === 0));

    expect($wave0)->toHaveCount(1)
        ->and($wave0[0]->key)->toBe('names')
        ->and($wave0[0]->type)->toBe(FieldType::NameList)
        ->and($wave0[0]->sensitive)->toBeFalse()
        ->and($wave0[0]->requiresSource)->toBeFalse();
});

test('a second sync changes nothing', function () {
    $sync = app(SyncFieldDefinitions::class);
    $sync->handle(base_path('data/fields'));
    $before = FieldDefinition::query()->orderBy('id')->get()->toArray();

    $this->travel(1)->days();
    $result = $sync->handle(base_path('data/fields'));

    expect($result->created)->toBe([])
        ->and($result->updated)->toBe([])
        ->and($result->unchanged)->toBe(12)
        ->and(FieldDefinition::query()->orderBy('id')->get()->toArray())->toBe($before);
});

test('a reworded label updates the definition in place', function () {
    $sync = app(SyncFieldDefinitions::class);
    $sync->handle(fieldDirectory(['wave1.v1.yaml' => sampleFieldFile()]));

    $result = $sync->handle(fieldDirectory(['wave1.v1.yaml' => sampleFieldFile(['"Sample choice"' => '"Reworded choice"'])]));

    expect($result->updated)->toBe(['sample_choice v1'])
        ->and(FieldDefinition::query()->where('key', 'sample_choice')->sole()->label)->toBe(['bn' => 'নমুনা বাছাই', 'en' => 'Reworded choice'])
        ->and(FieldDefinition::query()->count())->toBe(2);
});

test('a definition that no file lists is kept and reported', function () {
    FieldDefinition::factory()->create(['key' => 'old_field']);

    $result = app(SyncFieldDefinitions::class)->handle(fieldDirectory(['wave1.v1.yaml' => sampleFieldFile()]));

    expect($result->notInFiles)->toBe(['old_field v1'])
        ->and(FieldDefinition::query()->where('key', 'old_field')->exists())->toBeTrue();
});

test('the command syncs and reports', function () {
    $this->artisan('atlas:fields:sync')->expectsOutputToContain('12 created, 0 updated, 0 unchanged')->assertSuccessful();
    $this->artisan('atlas:fields:sync')->expectsOutputToContain('0 created, 0 updated, 12 unchanged')->assertSuccessful();
    $this->artisan('atlas:fields:sync', ['--path' => fieldDirectory([])])->expectsOutputToContain('no wave<N>.v<M>.yaml files')->assertFailed();
});

test('a field file that cannot be used as a whole syncs nothing', function (array $files, string $reason) {
    expect(fn () => app(SyncFieldDefinitions::class)->handle(fieldDirectory($files)))
        ->toThrow(InvalidFieldFile::class, $reason)
        ->and(FieldDefinition::query()->count())->toBe(0);
})->with([
    'wrong file name' => [['fields.yaml' => sampleFieldFile()], 'the name must be wave<N>.v<M>.yaml'],
    'not YAML' => [['wave1.v1.yaml' => "wave: 1\nfields: [\n"], 'Invalid field file'],
    'wave differs from the name' => [['wave2.v1.yaml' => sampleFieldFile()], 'must hold `wave: 2`'],
    'no fields' => [['wave1.v1.yaml' => "wave: 1\nfields: []\n"], '`fields` must be a non-empty list'],
    'unknown key' => [['wave1.v1.yaml' => sampleFieldFile(['    group: workload' => "    group: workload\n    colour: red"])], 'unknown keys: colour'],
    'bad key' => [['wave1.v1.yaml' => sampleFieldFile(['key: sample_scale' => 'key: Sample-Scale'])], 'needs a key'],
    'version 0' => [['wave1.v1.yaml' => sampleFieldFile(["sample_scale\n    version: 1" => "sample_scale\n    version: 0"])], 'needs a version of 1 or more'],
    'unknown type' => [['wave1.v1.yaml' => sampleFieldFile(['type: enum' => 'type: rating'])], 'needs a type'],
    'flag not boolean' => [['wave1.v1.yaml' => sampleFieldFile(['    group: workload' => "    group: workload\n    sensitive: \"yes\""])], 'sensitive must be true or false'],
    'missing English label' => [['wave1.v1.yaml' => sampleFieldFile(['{ bn: "নমুনা স্কেল", en: "Sample scale" }' => '{ bn: "নমুনা স্কেল" }'])], 'label needs text in exactly these locales: bn, en'],
    'extra locale' => [['wave1.v1.yaml' => sampleFieldFile(['{ bn: "নমুনা স্কেল", en: "Sample scale" }' => '{ bn: "নমুনা স্কেল", en: "Sample scale", fr: "Échelle" }'])], 'label needs text in exactly these locales'],
    'empty Bangla help' => [['wave1.v1.yaml' => sampleFieldFile(['help: { bn: "নমুনা সাহায্য", en: "Sample help" }' => 'help: { bn: " ", en: "Sample help" }'])], 'help has no bn text'],
    'four anchors' => [['wave1.v1.yaml' => sampleFieldFile(['      5: { bn: "পাঁচ", en: "Five" }'."\n" => ''])], 'needs anchors for 1, 2, 3, 4 and 5'],
    'anchor without Bangla' => [['wave1.v1.yaml' => sampleFieldFile(['{ bn: "তিন", en: "Three" }' => '{ en: "Three" }'])], 'anchor 3 needs text'],
    'anchors on an enum' => [['wave1.v1.yaml' => sampleFieldFile(['    options:' => "    anchors: { 1: { bn: a, en: a } }\n    options:"])], 'only a 1–5 scale has anchors'],
    'options on a scale' => [['wave1.v1.yaml' => sampleFieldFile(['    anchors:' => "    options: [{ key: a, bn: a, en: a }]\n    anchors:"])], 'a scale_1_5 field has no options'],
    'one option' => [['wave1.v1.yaml' => sampleFieldFile(['      - { key: second, bn: "দ্বিতীয়", en: "Second" }'."\n" => ''])], 'at least two options'],
    'duplicate option' => [['wave1.v1.yaml' => sampleFieldFile(['key: second' => 'key: first'])], 'option first is listed twice'],
    'unquoted no' => [['wave1.v1.yaml' => sampleFieldFile(['key: second' => 'key: false'])], 'quote yes and no'],
    'option without label' => [['wave1.v1.yaml' => sampleFieldFile(['{ key: second, bn: "দ্বিতীয়", en: "Second" }' => '{ key: second, bn: "দ্বিতীয়" }'])], 'option second needs text'],
    'key and version twice' => [['wave1.v1.yaml' => sampleFieldFile(), 'wave1.v2.yaml' => sampleFieldFile()], 'sample_scale v1 is already defined in wave1.v1.yaml'],
]);
