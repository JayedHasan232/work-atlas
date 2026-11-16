<?php

namespace App\Modules\Taxonomy\Data;

/**
 * A public occupation in a list: its slug, unit group and primary name in each language.
 */
final readonly class OccupationListItem
{
    /**
     * @param  array<string, string>  $names  primary name by locale
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $iscoCode,
        public array $names,
        public bool $indexable = false,
    ) {}
}
