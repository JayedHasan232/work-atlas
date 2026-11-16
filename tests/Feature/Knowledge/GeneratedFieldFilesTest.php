<?php

use App\Modules\Knowledge\Actions\FieldSchemaBuilder;
use Opis\JsonSchema\Validator;

test('the committed schemas and field table match the field files', function () {
    $this->artisan('atlas:fields:generate', ['--check' => true])
        ->expectsOutputToContain('Generated files are up to date')
        ->assertSuccessful();
});

test('the committed Wave 1 schema accepts an all-unknown answer and rejects a missing confidence', function () {
    $schema = (string) file_get_contents(base_path('data/schemas/wave1/fields.v1.json'));
    $keys = json_decode($schema, true)['properties']['fields']['required'];
    $unknown = array_fill_keys($keys, ['value' => FieldSchemaBuilder::UNKNOWN, 'confidence' => 0]);
    $validator = new Validator;

    expect($validator->validate(json_decode((string) json_encode(['fields' => $unknown])), $schema)->isValid())->toBeTrue();

    unset($unknown['physical_workload']['confidence']);

    expect($validator->validate(json_decode((string) json_encode(['fields' => $unknown])), $schema)->isValid())->toBeFalse();
});

test('a schema file is never overwritten', function () {
    $path = base_path('data/schemas/wave1/fields.v1.json');
    $original = (string) file_get_contents($path);
    file_put_contents($path, $original.' ');

    try {
        $this->artisan('atlas:fields:generate')
            ->expectsOutputToContain('schema files are immutable')
            ->assertFailed();

        expect(file_get_contents($path))->toBe($original.' ');
    } finally {
        file_put_contents($path, $original);
    }
});
