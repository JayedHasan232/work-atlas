<?php

use App\Modules\Knowledge\Actions\ReadFieldFiles;
use App\Modules\Knowledge\Actions\SyncFieldDefinitions;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Models\FieldDefinition;

test('every synced 1–5 scale has five anchors in Bangla and English', function () {
    app(SyncFieldDefinitions::class)->handle(base_path('data/fields'));
    $scales = FieldDefinition::query()->where('type', FieldType::Scale1To5)->get();

    expect($scales)->not->toBeEmpty();

    foreach ($scales as $scale) {
        expect(array_keys($scale->anchors ?? []))->toEqualCanonicalizing(['bn', 'en']);

        foreach (['bn', 'en'] as $locale) {
            expect(array_keys($scale->anchors[$locale]))->toBe([1, 2, 3, 4, 5], "{$scale->key} {$locale}");

            foreach ($scale->anchors[$locale] as $text) {
                expect(trim($text))->not->toBe('');
            }
        }
    }
});

test('every field and every option has a label in Bangla and English', function () {
    foreach (app(ReadFieldFiles::class)->handle(base_path('data/fields')) as $data) {
        expect(array_keys($data->label))->toBe(['bn', 'en'])
            ->and(array_keys($data->help))->toBe(['bn', 'en']);

        foreach ($data->options ?? [] as $option) {
            expect(array_keys($data->optionLabels[$option] ?? []))->toBe(['bn', 'en'], "{$data->key}.{$option}");
        }
    }
});

test('Bangla text in the field files uses Bangla digits', function () {
    foreach (app(ReadFieldFiles::class)->handle(base_path('data/fields')) as $data) {
        $bangla = [$data->label['bn'], $data->help['bn'], ...array_column($data->optionLabels ?? [], 'bn'), ...($data->anchors['bn'] ?? [])];

        foreach ($bangla as $text) {
            expect(preg_match('/[0-9]/', $text))->toBe(0, "{$data->key}: {$text}");
        }
    }
});
