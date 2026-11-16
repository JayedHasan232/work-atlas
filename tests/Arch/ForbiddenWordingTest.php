<?php

use Symfony\Component\Finder\Finder;

/*
 * Rules 6 and 11: no "perfect job for you", no "AI-proof" or "future-proof", no promised work or
 * income — not in the interface text and not in the AI prompts. Checked in both languages.
 */

/** @return array<string, string> */
function forbiddenWording(): array
{
    return [
        'perfect job' => '/\bperfect\s+(job|work|career|occupation|match|fit)\b/iu',
        'AI-proof' => '/\bA\.?I\.?[\s\-]*proof\b/iu',
        'future-proof' => '/\bfuture[\s\-]*proof\b/iu',
        'guaranteed work or income' => '/\bguarantee(d|s)?\s+(a\s+)?(job|work|income|earnings|employment)\b/iu',
        'নিখুঁত কাজ' => '/নিখুঁত\s*(কাজ|পেশা|চাকরি)/u',
        'এআই-প্রুফ' => '/(এআই|এ\.আই\.)[\s\-]*প্রুফ/u',
        'ভবিষ্যৎ-নিরাপদ' => '/ভবিষ্যৎ[\s\-]*(নিরাপদ|প্রুফ)/u',
        'নিশ্চিত চাকরি বা আয়' => '/(নিশ্চিত|গ্যারান্টি(যুক্ত)?)\s*(চাকরি|আয়|রোজগার|কাজ)/u',
    ];
}

it('keeps promises and "perfect job" wording out of the interface text and prompts', function () {
    $files = Finder::create()->files()->in([dirname(__DIR__, 2).'/lang', dirname(__DIR__, 2).'/data/prompts'])->name(['*.php', '*.json', '*.md']);
    $found = [];

    foreach ($files as $file) {
        foreach (forbiddenWording() as $name => $pattern) {
            if (preg_match($pattern, $file->getContents()) === 1) {
                $found[] = "{$file->getRelativePathname()}: {$name}";
            }
        }
    }

    expect($found)->toBe([])
        ->and(iterator_count($files))->toBeGreaterThan(20);
});

it('catches the forbidden wording', function (string $text) {
    $matches = array_filter(forbiddenWording(), fn (string $pattern): bool => preg_match($pattern, $text) === 1);

    expect($matches)->not->toBe([]);
})->with([
    'The perfect job for you',
    'An AI-proof career',
    'This work is AI proof',
    'Future-proof skills',
    'A guaranteed income',
    'আপনার জন্য নিখুঁত কাজ',
    'এআই-প্রুফ পেশা',
    'ভবিষ্যৎ-নিরাপদ পেশা',
    'নিশ্চিত আয়',
]);

it('lets ordinary words through', function (string $text) {
    $matches = array_filter(forbiddenWording(), fn (string $pattern): bool => preg_match($pattern, $text) === 1);

    expect($matches)->toBe([]);
})->with([
    'Matches your situation',
    'পাসওয়ার্ড দিয়ে নিশ্চিত করুন',
    'নিশ্চিত নই',
    'A forecast, with its assumptions',
]);
