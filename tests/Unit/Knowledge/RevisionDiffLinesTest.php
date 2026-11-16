<?php

use App\Modules\Knowledge\Queries\RevisionDiff;

it('diffs lines by their longest common part', function (array $old, array $new, array $expected) {
    $ops = array_map(fn (array $row): string => $row['op'].$row['line'], RevisionDiff::lines($old, $new));

    expect($ops)->toBe($expected);
})->with([
    'same' => [['a', 'b'], ['a', 'b'], [' a', ' b']],
    'all new' => [[], ['a'], ['+a']],
    'all gone' => [['a'], [], ['-a']],
    'middle changed' => [['a', 'b', 'c'], ['a', 'x', 'c'], [' a', '-b', '+x', ' c']],
    'inserted' => [['a', 'c'], ['a', 'b', 'c'], [' a', '+b', ' c']],
]);
