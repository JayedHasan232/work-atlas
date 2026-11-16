<?php

namespace App\Modules\Taxonomy\Data;

/**
 * What a prompt about one occupation needs to know (CLAUDE.md, "Context in every prompt"): its ISCO-08
 * unit group with the parents, and every name in Bangla and English.
 */
final readonly class OccupationContext
{
    /**
     * @param  array<string, list<string>>  $names  by locale; the primary name first, then the others as "name (type)"
     */
    public function __construct(
        public int $occupationId,
        public string $slug,
        public string $iscoCode,
        public string $hierarchy,
        public array $names,
    ) {}

    public function namesIn(string $locale): string
    {
        return implode('; ', $this->names[$locale] ?? []) ?: 'unknown';
    }
}
