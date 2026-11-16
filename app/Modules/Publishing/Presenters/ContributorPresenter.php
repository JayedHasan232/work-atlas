<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Community\Queries\PublicContributors;
use App\Modules\Knowledge\Queries\ContributionCredits;
use App\Modules\Taxonomy\Data\OccupationListItem;
use App\Modules\Taxonomy\Queries\PublicOccupations;

/**
 * A contributor's public profile (PLAN M16): their name, since when they contribute, how many of their
 * changes are part of the record, and the public occupations they worked on. Null for a person who
 * chose anonymous credit, or who does not exist.
 */
final class ContributorPresenter
{
    public function __construct(
        private readonly PublicContributors $contributors,
        private readonly ContributionCredits $credits,
        private readonly PublicOccupations $occupations,
        private readonly OccupationLinks $links,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function present(int $id, string $locale): ?array
    {
        $contributor = $this->contributors->find($id);

        if ($contributor === null) {
            return null;
        }

        return [
            'name' => $contributor->name,
            'since' => $contributor->since?->toIso8601String(),
            'contributions' => $this->credits->total($contributor->id),
            'occupations' => array_map(
                fn (OccupationListItem $item): array => $this->links->link($item, $locale),
                $this->occupations->findMany($this->credits->occupationsOf($contributor->id)),
            ),
        ];
    }
}
