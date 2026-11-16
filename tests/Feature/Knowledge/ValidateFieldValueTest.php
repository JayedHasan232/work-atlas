<?php

use App\Modules\Knowledge\Actions\ValidateFieldValue;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Exceptions\InvalidFieldValue;
use App\Modules\Knowledge\Models\FieldDefinition;

function fieldOfType(FieldType $type): FieldDefinition
{
    return new FieldDefinition([
        'key' => 'test_field',
        'type' => $type,
        'options' => in_array($type, [FieldType::Enum, FieldType::MultiEnum, FieldType::Band], true) ? ['a', 'b', 'c'] : null,
    ]);
}

function validateValue(FieldType $type, mixed $value, ?string $locale = null, ?string $region = null): mixed
{
    $locale ??= $type === FieldType::Text ? 'bn' : null;

    return app(ValidateFieldValue::class)->handle(fieldOfType($type), $value, $locale, $region);
}

it('accepts a value that fits the field and returns its stored form', function (FieldType $type, mixed $value, mixed $stored) {
    expect(validateValue($type, $value))->toBe($stored);
})->with([
    'scale low' => [FieldType::Scale1To5, 1, 1],
    'scale high' => [FieldType::Scale1To5, 5, 5],
    'enum' => [FieldType::Enum, 'b', 'b'],
    'band' => [FieldType::Band, 'c', 'c'],
    'multi enum, kept in the definition order' => [FieldType::MultiEnum, ['c', 'a'], ['a', 'c']],
    'boolean false' => [FieldType::Boolean, false, false],
    'text, trimmed' => [FieldType::Text, '  বিদ্যুতের লাইন মেরামত  ', 'বিদ্যুতের লাইন মেরামত'],
    'null on a scale' => [FieldType::Scale1To5, null, null],
    'unknown on a scale' => [FieldType::Scale1To5, 'unknown', null],
    'unknown on a boolean' => [FieldType::Boolean, 'unknown', null],
    'unknown on text' => [FieldType::Text, 'unknown', null],
]);

it('rejects a value that does not fit the field', function (FieldType $type, mixed $value) {
    expect(fn () => validateValue($type, $value))->toThrow(InvalidFieldValue::class);
})->with([
    'scale 0' => [FieldType::Scale1To5, 0],
    'scale 6' => [FieldType::Scale1To5, 6],
    'scale as a decimal' => [FieldType::Scale1To5, 3.5],
    'scale as text' => [FieldType::Scale1To5, '3'],
    'enum not in the options' => [FieldType::Enum, 'z'],
    'enum as a list' => [FieldType::Enum, ['a']],
    'band not in the options' => [FieldType::Band, 'z'],
    'multi enum empty' => [FieldType::MultiEnum, []],
    'multi enum with an unknown option' => [FieldType::MultiEnum, ['a', 'z']],
    'multi enum with a repeat' => [FieldType::MultiEnum, ['a', 'a']],
    'multi enum as a map' => [FieldType::MultiEnum, ['x' => 'a']],
    'boolean as a number' => [FieldType::Boolean, 1],
    'boolean as text' => [FieldType::Boolean, 'yes'],
    'text empty' => [FieldType::Text, '   '],
    'text as a number' => [FieldType::Text, 5],
    'text too long' => [FieldType::Text, str_repeat('ক', ValidateFieldValue::TEXT_MAX_LENGTH + 1)],
]);

it('needs a supported locale for text and none for other types', function (FieldType $type, ?string $locale) {
    expect(fn () => app(ValidateFieldValue::class)->handle(fieldOfType($type), null, $locale))
        ->toThrow(InvalidFieldValue::class);
})->with([
    'text without a locale' => [FieldType::Text, null],
    'text in an unsupported locale' => [FieldType::Text, 'fr'],
    'scale with a locale' => [FieldType::Scale1To5, 'bn'],
    'enum with a locale' => [FieldType::Enum, 'en'],
]);

it('accepts null or a short key as the region', function () {
    expect(validateValue(FieldType::Scale1To5, 3, region: 'sylhet'))->toBe(3);
    expect(fn () => validateValue(FieldType::Scale1To5, 3, region: ' '))->toThrow(InvalidFieldValue::class);
    expect(fn () => validateValue(FieldType::Scale1To5, 3, region: str_repeat('x', 33)))->toThrow(InvalidFieldValue::class);
});
