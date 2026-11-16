<?php

use App\Modules\AiPipeline\Actions\AgreementCalculator;
use App\Modules\Knowledge\Enums\FieldType;

/*
 * PLAN M12 (D50): when two models' values count as the same. A disagreement puts the value at the top
 * of the review queue.
 */

it('compares two values by field type', function (FieldType $type, mixed $a, mixed $b, bool $agree) {
    $bands = ['under_1_month', 'months_1_3', 'months_3_12', 'years_1_2'];

    expect((new AgreementCalculator)->handle($type, $a, $b, $bands))->toBe($agree)
        ->and((new AgreementCalculator)->handle($type, $b, $a, $bands))->toBe($agree);
})->with([
    'scale, same' => [FieldType::Scale1To5, 3, 3, true],
    'scale, 1 apart' => [FieldType::Scale1To5, 3, 4, true],
    'scale, 2 apart' => [FieldType::Scale1To5, 2, 4, false],
    'enum, same' => [FieldType::Enum, 'ssc', 'ssc', true],
    'enum, different' => [FieldType::Enum, 'ssc', 'hsc', false],
    'boolean, same' => [FieldType::Boolean, true, true, true],
    'boolean, different' => [FieldType::Boolean, true, false, false],
    'band, same' => [FieldType::Band, 'months_1_3', 'months_1_3', true],
    'band, neighbours' => [FieldType::Band, 'months_1_3', 'months_3_12', true],
    'band, two apart' => [FieldType::Band, 'under_1_month', 'months_3_12', false],
    'band, not an option' => [FieldType::Band, 'months_1_3', 'decades', false],
    'options, same set' => [FieldType::MultiEnum, ['indoor', 'outdoor'], ['outdoor', 'indoor'], true],
    'options, half shared' => [FieldType::MultiEnum, ['indoor'], ['indoor', 'outdoor'], true],
    'options, a third shared' => [FieldType::MultiEnum, ['indoor', 'home_based'], ['indoor', 'outdoor'], false],
    'options, none shared' => [FieldType::MultiEnum, ['day'], ['night'], false],
    'both unknown' => [FieldType::Scale1To5, 'unknown', 'unknown', true],
    'unknown and null' => [FieldType::Enum, 'unknown', null, true],
    'one unknown' => [FieldType::Scale1To5, 'unknown', 3, false],
    'one unknown option list' => [FieldType::MultiEnum, ['day'], 'unknown', false],
]);
