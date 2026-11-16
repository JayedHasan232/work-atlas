<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Knowledge\Data\FieldSlot;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Queries\OccupationFields;
use App\Modules\Taxonomy\Data\OccupationListItem;
use App\Modules\Taxonomy\Queries\OccupationBySlug;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use App\Modules\Taxonomy\Queries\SearchOccupations;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Config;

/**
 * Two or three occupations side by side (PLAN M20): the basic facts of each, with the status of every
 * value, and a search to add one more. The sensitive-field gate holds here as on the page.
 */
final class ComparePresenter
{
    public const int MAX = 3;

    public function __construct(
        private readonly OccupationBySlug $slugs,
        private readonly PublicOccupations $occupations,
        private readonly OccupationFields $fields,
        private readonly OccupationLinks $links,
        private readonly FieldDisplay $display,
        private readonly SearchOccupations $search,
    ) {}

    /**
     * @param  list<string>  $slugs
     * @return array<string, mixed>
     */
    public function present(array $slugs, ?string $query, string $locale): array
    {
        $occupations = $this->resolve($slugs);
        $chosen = array_map(fn (OccupationListItem $item): string => $item->slug, $occupations);
        /** @var list<int> $waves */
        $waves = Config::array('atlas.discovery.compare_waves');

        $rows = [];

        foreach ($occupations as $column => $occupation) {
            foreach ($this->fields->handle($occupation->id, $waves) as $slot) {
                if (in_array($slot->definition->type, [FieldType::NameList, FieldType::Text], true)) {
                    continue;
                }

                $key = $slot->definition->key;
                $rows[$key] ??= [
                    'key' => $key,
                    'label' => $this->display->text($slot->definition->label, $locale) ?? $key,
                    'help' => $this->display->text($slot->definition->help, $locale),
                    'cells' => array_fill(0, count($occupations), null),
                ];
                $rows[$key]['cells'][$column] = $this->cell($slot, $locale);
            }
        }

        return [
            'occupations' => array_map(fn (OccupationListItem $item): array => [
                ...$this->links->link($item, $locale),
                'removeUrl' => $this->url(array_values(array_diff($chosen, [$item->slug])), $locale),
            ], $occupations),
            'rows' => array_values($rows),
            'max' => self::MAX,
            'query' => $query,
            'hits' => $query === null || count($occupations) >= self::MAX ? [] : $this->hits($query, $chosen, $locale),
            'searchUrl' => $this->url($chosen, $locale),
            'chosen' => $chosen,
        ];
    }

    /**
     * The public occupations behind the slugs, in order, without repeats; a merged one counts as the
     * occupation it was merged into.
     *
     * @param  list<string>  $slugs
     * @return list<OccupationListItem>
     */
    private function resolve(array $slugs): array
    {
        $found = [];

        foreach ($slugs as $slug) {
            $address = $this->slugs->handle($slug);
            $item = $address === null ? null : $this->occupations->find($address->occupationId);

            if ($item !== null && ! isset($found[$item->id])) {
                $found[$item->id] = $item;
            }
        }

        return array_slice(array_values($found), 0, self::MAX);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function cell(FieldSlot $slot, string $locale): ?array
    {
        $value = $this->display->pick($slot->values, $locale);

        if ($value === null) {
            return null;
        }

        // The sensitive-field gate (CLAUDE.md, SensitiveFieldGateTest).
        if ($slot->definition->sensitive && $value->status !== FieldStatus::Verified) {
            return ['value' => null, 'status' => $value->status->value, 'withheld' => true];
        }

        return [
            'value' => $this->display->display($slot->definition, $value->value, $locale),
            'status' => $value->status->value,
            'withheld' => false,
        ];
    }

    /**
     * @param  list<string>  $chosen
     * @return list<array<string, mixed>>
     */
    private function hits(string $query, array $chosen, string $locale): array
    {
        $hits = [];

        foreach ($this->search->handle($query, $locale, 6) as $hit) {
            if (in_array($hit->occupation->slug, $chosen, true)) {
                continue;
            }

            $hits[] = [
                ...$this->links->link($hit->occupation, $locale),
                'matchedName' => $hit->matchedName,
                'addUrl' => $this->url([...$chosen, $hit->occupation->slug], $locale),
            ];
        }

        return array_slice($hits, 0, 5);
    }

    /**
     * @param  list<string>  $slugs
     */
    private function url(array $slugs, string $locale): string
    {
        $path = LocalizedRoutes::path('compare.index', [], $locale);

        return $slugs === [] ? $path : $path.'?'.http_build_query(['o' => $slugs]);
    }
}
