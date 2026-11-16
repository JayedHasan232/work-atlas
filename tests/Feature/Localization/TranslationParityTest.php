<?php

use App\Support\Localization\TranslationFiles;
use App\Support\Localization\TranslationParity;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Copies lang/ to a temporary folder, lets the test change one group of one locale, and returns
 * the folder.
 *
 * @param  Closure(array<array-key, mixed>): array<array-key, mixed>  $change
 */
function langCopyWith(string $locale, string $group, Closure $change): string
{
    $path = langCopyPrefix().bin2hex(random_bytes(4));
    File::copyDirectory(lang_path(), $path);

    $lines = (new TranslationFiles($path))->group($locale, $group);
    File::put("{$path}/{$locale}/{$group}.php", '<?php return '.var_export($change($lines), true).';');

    return $path;
}

/** Copies made by this test process; the process id keeps parallel runs apart. */
function langCopyPrefix(): string
{
    return sys_get_temp_dir().'/work-atlas-lang-'.getmypid().'-';
}

/**
 * @return array{exitCode: int, output: string}
 */
function runTranslationCheck(?string $langPath = null): array
{
    $result = Process::run(array_filter([PHP_BINARY, base_path('scripts/ci/check-translations'), $langPath]));

    return ['exitCode' => (int) $result->exitCode(), 'output' => $result->output().$result->errorOutput()];
}

afterEach(function () {
    foreach (glob(langCopyPrefix().'*', GLOB_ONLYDIR) ?: [] as $path) {
        File::deleteDirectory($path);
    }
});

test('the project translations agree', function () {
    expect((new TranslationParity(new TranslationFiles(lang_path())))->problems())->toBe([]);

    $check = runTranslationCheck();

    expect($check['exitCode'])->toBe(0)
        ->and($check['output'])->toContain('Translations agree: bn, en');
});

test('removing one key from lang/bn fails the check', function () {
    $path = langCopyWith('bn', 'common', function (array $lines): array {
        Arr::forget($lines, 'nav.home');

        return $lines;
    });

    expect((new TranslationParity(new TranslationFiles($path)))->problems())
        ->toBe(['[bn] missing: common.nav.home']);

    $check = runTranslationCheck($path);

    expect($check['exitCode'])->toBe(1)
        ->and($check['output'])->toContain('[bn] missing: common.nav.home');
});

test('a key only one locale has is reported too', function () {
    $path = langCopyWith('bn', 'common', fn (array $lines): array => Arr::add($lines, 'nav.extra', 'অতিরিক্ত'));

    expect((new TranslationParity(new TranslationFiles($path)))->problems())
        ->toBe(['[en] missing: common.nav.extra']);
});

test('a line with different placeholders is reported', function () {
    $path = langCopyWith('bn', 'common', function (array $lines): array {
        Arr::set($lines, 'footer.copyright', '© Work Atlas-এর অবদানকারীরা');

        return $lines;
    });

    expect((new TranslationParity(new TranslationFiles($path)))->problems())
        ->toBe(['placeholders differ: common.footer.copyright (bn: (none); en: :year)']);
});

test('an empty line is reported', function () {
    $path = langCopyWith('bn', 'home', fn (array $lines): array => [...$lines, 'title' => ' ']);

    expect((new TranslationParity(new TranslationFiles($path)))->problems())
        ->toBe(['[bn] empty or not text: home.title']);
});

test('placeholders are compared without case and order', function () {
    expect(TranslationParity::placeholders(':Attribute must be :min, not :attribute'))
        ->toBe([':attribute', ':min'])
        ->and(TranslationParity::placeholders('Text and data: https://example.org'))
        ->toBe([]);
});
