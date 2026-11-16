<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Taxonomy\Data\OccupationListItem;
use App\Support\Localization\LocalizedRoutes;

/**
 * An occupation as a link in a list (browse, search): its primary name in the reader's language,
 * else in another language, and the URL of its page in the reader's language.
 */
final class OccupationLinks
{
    /**
     * @return array{slug: string, iscoCode: string, name: string, nameLocale: string, url: string}
     */
    public function link(OccupationListItem $item, string $locale): array
    {
        $nameLocale = isset($item->names[$locale]) ? $locale : array_key_first($item->names);

        return [
            'slug' => $item->slug,
            'iscoCode' => $item->iscoCode,
            'name' => $nameLocale === null ? $item->slug : $item->names[$nameLocale],
            'nameLocale' => $nameLocale ?? 'en',
            'url' => LocalizedRoutes::path('occupations.show', ['slug' => $item->slug], $locale),
        ];
    }
}
