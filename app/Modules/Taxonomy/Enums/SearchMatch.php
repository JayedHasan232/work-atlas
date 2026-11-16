<?php

namespace App\Modules\Taxonomy\Enums;

/**
 * How a name matched a search, best first (PLAN M15, D59).
 */
enum SearchMatch: string
{
    /** The whole name, after normalization. */
    case Exact = 'exact';

    /** The name, or one of its words, starts with the query. */
    case Prefix = 'prefix';

    /** Close by trigram similarity: a misspelling or a variant. */
    case Similar = 'similar';

    public static function fromTier(int $tier): self
    {
        return match ($tier) {
            3 => self::Exact,
            2 => self::Prefix,
            default => self::Similar,
        };
    }
}
