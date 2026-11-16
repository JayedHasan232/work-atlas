<?php

use App\Modules\Taxonomy\Actions\NormalizeForSearch;
use App\Modules\Taxonomy\Actions\NormalizeName;

/*
 * PLAN M15: the search key folds what people type differently for the same word, so a query finds a
 * name however either was spelled.
 */

function searchKey(string $text): string
{
    return (new NormalizeForSearch(new NormalizeName))->handle($text);
}

it('gives two spellings of one word the same key', function (string $a, string $b) {
    expect(searchKey($a))->toBe(searchKey($b));
})->with([
    'long and short i' => ['মিস্ত্রী', 'মিস্ত্রি'],
    'long and short u' => ['দূত', 'দুত'],
    'initial long i' => ['ঈদ', 'ইদ'],
    'sibilants' => ['শিক্ষক', 'সিক্ষক'],
    'retroflex sibilant' => ['কৃষক', 'কৃসক'],
    'retroflex n' => ['কারিগরি প্রশিক্ষণ', 'কারিগরি প্রসিক্ষন'],
    'khanda ta' => ['বিদ্যুৎ মিস্ত্রি', 'বিদ্যুত মিস্ত্রি'],
    'ta with hasanta' => ['বিদ্যুত্ মিস্ত্রি', 'বিদ্যুৎ মিস্ত্রি'],
    'chandrabindu' => ['চাঁদ', 'চাদ'],
    'precomposed and decomposed ya' => ["\u{09DF}", "\u{09AF}\u{09BC}"],
    'zero-width joiner' => ["র\u{200D}্যাব", 'র্যাব'],
    'zero-width non-joiner' => ["কিস্\u{200C}তি", 'কিস্তি'],
    'Bangla and ASCII digits' => ['১২ ঘণ্টা', '12 ঘন্টা'],
    'English case and spaces' => ['  Electrician ', 'electrician'],
    'punctuation' => ['ড্রাইভার (ট্রাক)', 'ড্রাইভার ট্রাক'],
]);

it('keeps different words apart', function (string $a, string $b) {
    expect(searchKey($a))->not->toBe(searchKey($b));
})->with([
    'ড and ড়' => ['বড', 'বড়'],
    'different consonants' => ['কাজ', 'কাচ'],
    'mid-word hasanta stays' => ['পাক্কা', 'পাককা'],
]);

it('turns a query of only punctuation into an empty key', function () {
    expect(searchKey('।?!'))->toBe('');
});
