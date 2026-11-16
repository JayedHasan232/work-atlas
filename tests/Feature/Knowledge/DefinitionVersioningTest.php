<?php

use App\Modules\Knowledge\Actions\SyncFieldDefinitions;
use App\Modules\Knowledge\Exceptions\DefinitionChangeNeedsNewVersion;
use App\Modules\Knowledge\Models\FieldDefinition;

function syncSample(array $changes = [], array $extraFiles = []): mixed
{
    return app(SyncFieldDefinitions::class)->handle(fieldDirectory(['wave1.v1.yaml' => sampleFieldFile($changes), ...$extraFiles]));
}

function sampleDefinition(string $key = 'sample_scale', int $version = 1): FieldDefinition
{
    return FieldDefinition::query()->where('key', $key)->where('version', $version)->sole();
}

test('the meaning of a definition with values cannot change in place', function (array $changes, string $key, string $columns) {
    syncSample();
    recordValue(sampleDefinition($key), $key === 'sample_scale' ? 3 : 'first');
    $before = sampleDefinition($key)->toArray();

    expect(fn () => syncSample($changes))
        ->toThrow(DefinitionChangeNeedsNewVersion::class, "Field {$key} v1 already has values, so its {$columns} cannot change. Add version 2 instead.")
        ->and(sampleDefinition($key)->toArray())->toBe($before);
})->with([
    'anchor reworded' => [['{ bn: "তিন", en: "Three" }' => '{ bn: "তিন", en: "Three, changed" }'], 'sample_scale', 'anchors'],
    'option added' => [['      - { key: second, bn: "দ্বিতীয়", en: "Second" }' => "      - { key: second, bn: \"দ্বিতীয়\", en: \"Second\" }\n      - { key: third, bn: \"তৃতীয়\", en: \"Third\" }"], 'sample_choice', 'options'],
    'type changed' => [['type: enum' => 'type: band'], 'sample_choice', 'type'],
]);

test('a failed sync changes no other definition either', function () {
    syncSample();
    recordValue(sampleDefinition('sample_choice'), 'first');

    expect(fn () => syncSample(['"Sample scale"' => '"Renamed scale"', 'type: enum' => 'type: band']))
        ->toThrow(DefinitionChangeNeedsNewVersion::class)
        ->and(sampleDefinition()->label['en'])->toBe('Sample scale');
});

test('labels and help of a definition with values can be reworded', function () {
    syncSample();
    recordValue(sampleDefinition('sample_choice'), 'first');

    $result = syncSample(['"Second"' => '"Second, reworded"', 'help: { bn: "নমুনা সাহায্য", en: "Sample help" }' => 'help: { bn: "নতুন সাহায্য", en: "New help" }']);

    expect($result->updated)->toBe(['sample_scale v1', 'sample_choice v1'])
        ->and(sampleDefinition('sample_choice')->option_labels['second']['en'])->toBe('Second, reworded');
});

test('a definition without values can still change its meaning', function () {
    syncSample();

    $result = syncSample(['{ bn: "তিন", en: "Three" }' => '{ bn: "তিন", en: "Three, changed" }']);

    expect($result->updated)->toBe(['sample_scale v1'])
        ->and(sampleDefinition()->anchors['en'][3])->toBe('Three, changed');
});

test('a new version is a new definition and the old one stays with its values', function () {
    syncSample();
    $value = recordValue(sampleDefinition(), 3);
    $v2 = sampleFieldFile([
        "sample_scale\n    version: 1" => "sample_scale\n    version: 2",
        "sample_choice\n    version: 1" => "sample_choice\n    version: 2",
        '{ bn: "তিন", en: "Three" }' => '{ bn: "তিন", en: "Three, changed" }',
    ]);

    $result = syncSample(extraFiles: ['wave1.v2.yaml' => $v2]);

    expect($result->created)->toBe(['sample_scale v2', 'sample_choice v2'])
        ->and(sampleDefinition(version: 1)->anchors['en'][3])->toBe('Three')
        ->and(sampleDefinition(version: 2)->anchors['en'][3])->toBe('Three, changed')
        ->and($value->fieldValue->field_definition_id)->toBe(sampleDefinition(version: 1)->id);
});
