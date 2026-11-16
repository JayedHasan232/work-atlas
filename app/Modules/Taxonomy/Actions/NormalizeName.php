<?php

namespace App\Modules\Taxonomy\Actions;

use Normalizer;

/**
 * The search key of a name (`occupation_names.normalized_name`, D6). Two spellings that differ only
 * in Unicode form, case, zero-width joiners, punctuation or spacing get the same key:
 *
 * - NFKC, so precomposed and decomposed Bangla letters (য়, ড়, ঢ় typed either way) match;
 * - zero-width joiner and non-joiner and soft hyphens are removed (they vary with the keyboard);
 * - lower case; punctuation and symbols (including the danda ।) become spaces; spaces collapse.
 *
 * Bangla spelling variants beyond this (ী/ি, ূ/ু and the like) wait for the search benchmark (M15).
 */
final class NormalizeName
{
    public function handle(string $name): string
    {
        $key = Normalizer::normalize($name, Normalizer::FORM_KC);
        $key = str_replace(["\u{200C}", "\u{200D}", "\u{00AD}"], '', is_string($key) ? $key : $name);
        $key = mb_strtolower($key);
        $key = trim((string) preg_replace(['/[\p{P}\p{S}]+/u', '/\s+/u'], ' ', $key));

        return $key !== '' ? $key : mb_strtolower(trim($name));
    }
}
