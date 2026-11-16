<?php

namespace App\Modules\Taxonomy\Actions;

/**
 * The loose search key of a name or a query (`occupation_names.search_key`, PLAN M15, D58). It starts
 * from the name's normalized key (NormalizeName: Unicode form, case, joiners, punctuation, spaces)
 * and also folds what people type differently for the same word:
 *
 * - Bangla digits become ASCII digits (২৪ → 24);
 * - long and short vowels: ী → ি, ূ → ু, ঈ → ই, ঊ → উ;
 * - the three sibilants শ, ষ → স, and ণ → ন;
 * - khanda ta ৎ → ত, and a hasanta at the end of a word is dropped (বিদ্যুৎ, বিদ্যুত, বিদ্যুত্ match);
 * - chandrabindu ঁ is dropped.
 *
 * The duplicate detector keeps the stricter `normalized_name`, so this folding never merges names.
 */
final class NormalizeForSearch
{
    private const array FOLD = [
        '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
        '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
        'ী' => 'ি', 'ূ' => 'ু', 'ঈ' => 'ই', 'ঊ' => 'উ',
        'শ' => 'স', 'ষ' => 'স', 'ণ' => 'ন',
        'ৎ' => 'ত', 'ঁ' => '',
    ];

    public function __construct(private readonly NormalizeName $normalize) {}

    public function handle(string $text): string
    {
        $key = strtr($this->normalize->handle($text), self::FOLD);

        // NormalizeName keeps a name of only punctuation as it is; a query like that finds nothing.
        if (preg_match('/[\p{L}\p{N}]/u', $key) !== 1) {
            return '';
        }

        // A hasanta (virama) that ends a word: বিদ্যুত্ → বিদ্যুত.
        return trim((string) preg_replace(['/\x{09CD}(?=\s|$)/u', '/\s+/u'], ['', ' '], $key));
    }
}
