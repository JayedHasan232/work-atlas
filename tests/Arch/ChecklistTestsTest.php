<?php

/*
 * PLAN M23: every "Data and code" checklist item is proved by a test named in
 * docs/architecture/checklist-tests.md. A test named there must exist, and every item that a test
 * can prove names at least one.
 */

/** @return array<int, string> the table row of each checklist item, by its number */
function checklistRows(): array
{
    $doc = file_get_contents(dirname(__DIR__, 2).'/docs/architecture/checklist-tests.md') ?: '';
    preg_match_all('/^\| (\d) \| .+$/m', $doc, $rows);

    return array_combine($rows[1], $rows[0]);
}

test('the map covers all six checklist items', function () {
    expect(array_keys(checklistRows()))->toBe([1, 2, 3, 4, 5, 6]);
});

test('every test the map names exists', function () {
    $doc = file_get_contents(dirname(__DIR__, 2).'/docs/architecture/checklist-tests.md') ?: '';
    preg_match_all('/`((?:tests\/[\w\/]+Test\.php)|(?:scripts\/ci\/[\w-]+))`/', $doc, $paths);

    expect($paths[1])->not->toBeEmpty();

    foreach ($paths[1] as $path) {
        expect(dirname(__DIR__, 2).'/'.$path)->toBeFile();
    }
});

test('each provable item names at least one test', function () {
    foreach (checklistRows() as $item => $row) {
        if ($item === 6) {
            expect($row)->toContain('Not provable by a test');

            continue;
        }

        expect(preg_match('/`tests\/[\w\/]+Test\.php`/', $row))->toBe(1, "Checklist item {$item} names no test.");
    }
});
