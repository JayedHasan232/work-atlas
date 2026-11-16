<?php

use App\Modules\Knowledge\Actions\FieldSchemaBuilder;
use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Enums\FieldType;
use Opis\JsonSchema\Validator;

function schemaField(string $key, FieldType $type, ?array $options = null): FieldDefinitionData
{
    return new FieldDefinitionData(
        key: $key,
        version: 1,
        wave: 1,
        group: 'test',
        type: $type,
        label: ['bn' => 'নমুনা', 'en' => 'Sample'],
        help: ['bn' => 'নমুনা', 'en' => 'Sample help'],
        options: $options,
    );
}

/**
 * @return array<string, mixed>
 */
function sampleSchema(): array
{
    return (new FieldSchemaBuilder)->handle([
        schemaField('workload', FieldType::Scale1To5),
        schemaField('education', FieldType::Enum, ['none', 'ssc']),
        schemaField('setting', FieldType::MultiEnum, ['indoor', 'outdoor']),
        schemaField('time_to_learn', FieldType::Band, ['short', 'long']),
        schemaField('licensed', FieldType::Boolean),
        schemaField('summary', FieldType::Text),
    ], 1);
}

function validSample(): array
{
    return ['fields' => [
        'workload' => ['value' => 4, 'confidence' => 0.8],
        'education' => ['value' => 'ssc', 'confidence' => 0.6, 'note' => 'Usually SSC.'],
        'setting' => ['value' => ['indoor', 'outdoor'], 'confidence' => 0.7],
        'time_to_learn' => ['value' => 'unknown', 'confidence' => 0],
        'licensed' => ['value' => false, 'confidence' => 1],
        'summary' => ['value' => 'Fixes wiring.', 'confidence' => 0.5],
    ]];
}

function schemaAccepts(array $output): bool
{
    $data = json_decode((string) json_encode($output));

    return (new Validator)->validate($data, (string) json_encode(sampleSchema()))->isValid();
}

test('the schema accepts a valid answer, including "unknown"', function () {
    expect(schemaAccepts(validSample()))->toBeTrue();
});

test('the schema rejects answers the field does not allow', function (Closure $change) {
    $output = validSample();
    $change($output);

    expect(schemaAccepts($output))->toBeFalse();
})->with([
    'scale above 5' => [function (array &$o) {
        $o['fields']['workload']['value'] = 6;
    }],
    'scale below 1' => [function (array &$o) {
        $o['fields']['workload']['value'] = 0;
    }],
    'scale not whole' => [function (array &$o) {
        $o['fields']['workload']['value'] = 2.5;
    }],
    'scale as text' => [function (array &$o) {
        $o['fields']['workload']['value'] = '4';
    }],
    'missing confidence' => [function (array &$o) {
        unset($o['fields']['workload']['confidence']);
    }],
    'confidence above 1' => [function (array &$o) {
        $o['fields']['workload']['confidence'] = 1.2;
    }],
    'confidence below 0' => [function (array &$o) {
        $o['fields']['workload']['confidence'] = -0.1;
    }],
    'missing value' => [function (array &$o) {
        unset($o['fields']['education']['value']);
    }],
    'unknown option' => [function (array &$o) {
        $o['fields']['education']['value'] = 'phd';
    }],
    'empty multi-choice' => [function (array &$o) {
        $o['fields']['setting']['value'] = [];
    }],
    'repeated multi-choice' => [function (array &$o) {
        $o['fields']['setting']['value'] = ['indoor', 'indoor'];
    }],
    'band outside its options' => [function (array &$o) {
        $o['fields']['time_to_learn']['value'] = 'medium';
    }],
    'boolean as text' => [function (array &$o) {
        $o['fields']['licensed']['value'] = 'no';
    }],
    'empty text' => [function (array &$o) {
        $o['fields']['summary']['value'] = '';
    }],
    'note too long' => [function (array &$o) {
        $o['fields']['education']['note'] = str_repeat('a', FieldSchemaBuilder::NOTE_MAX_LENGTH + 1);
    }],
    'extra key in an answer' => [function (array &$o) {
        $o['fields']['workload']['source'] = 'https://example.test';
    }],
    'missing field' => [function (array &$o) {
        unset($o['fields']['summary']);
    }],
    'field not in the wave' => [function (array &$o) {
        $o['fields']['income'] = ['value' => 'unknown', 'confidence' => 0];
    }],
    'nothing at the top' => [function (array &$o) {
        $o = [];
    }],
    'extra top-level key' => [function (array &$o) {
        $o['occupation'] = 'electrician';
    }],
]);

test('the schema is draft 2020-12 with a versioned id', function () {
    expect(sampleSchema())
        ->toHaveKey('$schema', 'https://json-schema.org/draft/2020-12/schema')
        ->toHaveKey('$id', 'urn:work-atlas:schema:wave1:fields:v1');
});

test('a schema needs fields of one wave, one per key', function (array $fields) {
    expect(fn () => (new FieldSchemaBuilder)->handle($fields, 1))->toThrow(InvalidArgumentException::class);
})->with([
    'none' => [[]],
    'same key twice' => [[schemaField('a', FieldType::Boolean), schemaField('a', FieldType::Boolean)]],
    'two waves' => [[schemaField('a', FieldType::Boolean), new FieldDefinitionData('b', 1, 2, 'test', FieldType::Boolean, ['en' => 'b'], ['en' => 'b'])]],
]);
