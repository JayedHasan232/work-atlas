<?php

namespace App\Modules\Taxonomy\Data;

/**
 * An ISCO-08 group for public pages. The Bangla title can still be missing (M6b).
 */
final readonly class IscoGroupData
{
    public function __construct(
        public string $code,
        public int $level,
        public string $titleEn,
        public ?string $titleBn,
    ) {}

    /** The title in the language, the English one while the Bangla title is missing. */
    public function title(string $locale): string
    {
        return $locale === 'bn' && $this->titleBn !== null ? $this->titleBn : $this->titleEn;
    }

    /** The language the title is actually in. */
    public function titleLocale(string $locale): string
    {
        return $locale === 'bn' && $this->titleBn !== null ? 'bn' : 'en';
    }
}
