<?php

namespace App\Modules\Taxonomy\Data;

final readonly class IscoImportResult
{
    /**
     * @param  array<int, int>  $groupsPerLevel  groups in the file, by level 1–4
     */
    public function __construct(
        public int $created,
        public int $updated,
        public int $unchanged,
        public array $groupsPerLevel,
        public int $sourceId,
    ) {}

    public function changedAnything(): bool
    {
        return $this->created + $this->updated > 0;
    }
}
