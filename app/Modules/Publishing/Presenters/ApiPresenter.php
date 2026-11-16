<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Knowledge\Data\CitedSource;
use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Data\FieldSlot;
use App\Modules\Knowledge\Data\SlotValue;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Queries\OccupationFields;
use App\Modules\Knowledge\Queries\PublicFieldDefinitions;
use App\Modules\Taxonomy\Data\IscoGroupData;
use App\Modules\Taxonomy\Data\OccupationListItem;
use App\Modules\Taxonomy\Data\SearchHit;
use App\Modules\Taxonomy\Queries\IscoGroups;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use App\Modules\Taxonomy\Queries\SearchOccupations;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Config;

/**
 * The public read-only API v1 (PLAN M21). The same read path as the pages: only public occupations,
 * only the fields of the public waves, and the sensitive-field gate — a sensitive value that is not
 * `verified` keeps its status but leaves out its value and sources. Nothing about people (authors,
 * credits, accounts), no AI run output and no private submissions (D75). Every response carries the
 * license and the attribution.
 */
final class ApiPresenter
{
    public function __construct(
        private readonly PublicOccupations $occupations,
        private readonly OccupationFields $fields,
        private readonly PublicFieldDefinitions $definitions,
        private readonly IscoGroups $groups,
        private readonly SearchOccupations $search,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function occupations(int $page, int $perPage, ?string $group): array
    {
        $index = $this->occupations->index(Config::string('app.locale'), $page, $perPage, $group);
        $link = fn (int $page): string => route('api.v1.occupations.index', array_filter([
            'group' => $group,
            'page' => $page,
            'per_page' => $perPage,
        ], fn (mixed $value): bool => $value !== null));

        return [
            'data' => array_map(fn (OccupationListItem $item): array => $this->summary($item), $index->items),
            'meta' => $this->meta() + [
                'pagination' => [
                    'page' => $index->page,
                    'per_page' => $perPage,
                    'total' => $index->total,
                    'last_page' => $index->lastPage,
                ],
            ],
            'links' => [
                'self' => $link($index->page),
                'prev' => $index->page > 1 ? $link($index->page - 1) : null,
                'next' => $index->page < $index->lastPage ? $link($index->page + 1) : null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function occupation(OccupationListItem $occupation): array
    {
        $sources = [];
        $fields = [];

        foreach ($this->fields->handle($occupation->id, $this->waves()) as $slot) {
            $fields[] = $this->field($slot, $sources);
        }

        ksort($sources);

        return [
            'data' => $this->summary($occupation) + [
                'groups' => array_map(fn (IscoGroupData $group): array => [
                    'code' => $group->code,
                    'title' => array_filter(['bn' => $group->titleBn, 'en' => $group->titleEn], fn (?string $title): bool => $title !== null),
                ], $this->groups->path($occupation->iscoCode)),
                'fields' => $fields,
                'sources' => array_values($sources),
            ],
            'meta' => $this->meta(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        return [
            'data' => array_map(fn (FieldDefinitionData $field): array => [
                'key' => $field->key,
                'version' => $field->version,
                'wave' => $field->wave,
                'group' => $field->group,
                'type' => $field->type->value,
                'label' => (object) $field->label,
                'help' => (object) $field->help,
                'options' => $field->options,
                'option_labels' => $field->optionLabels === null ? null : (object) $field->optionLabels,
                'anchors' => $field->anchors === null ? null : (object) $field->anchors,
                'sensitive' => $field->sensitive,
                'requires_source' => $field->requiresSource,
            ], $this->definitions->handle($this->waves())),
            'meta' => $this->meta(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function search(string $query, string $locale, int $limit): array
    {
        return [
            'data' => array_map(fn (SearchHit $hit): array => $this->summary($hit->occupation) + [
                'matched_name' => $hit->matchedName,
                'matched_locale' => $hit->matchedLocale,
                'match' => $hit->match->value,
            ], $this->search->handle($query, $locale, $limit)),
            'meta' => $this->meta(),
        ];
    }

    /**
     * @return array{license: string, license_url: string, attribution: string, notice: string}
     */
    public function meta(): array
    {
        return [
            'license' => Config::string('atlas.api.license'),
            'license_url' => Config::string('atlas.api.license_url'),
            'attribution' => Config::string('atlas.api.attribution'),
            'notice' => Config::string('atlas.api.notice'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(OccupationListItem $occupation): array
    {
        return [
            'slug' => $occupation->slug,
            'isco_code' => $occupation->iscoCode,
            'names' => (object) $occupation->names,
            'url' => route('api.v1.occupations.show', ['slug' => $occupation->slug]),
            'page_url' => url(LocalizedRoutes::path('occupations.show', ['slug' => $occupation->slug], Config::string('app.locale'))),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $sources  collects the cited sources by id
     * @return array<string, mixed>
     */
    private function field(FieldSlot $slot, array &$sources): array
    {
        $definition = $slot->definition;

        return [
            'key' => $definition->key,
            'version' => $definition->version,
            'group' => $definition->group,
            'type' => $definition->type->value,
            'values' => array_map(function (SlotValue $value) use ($definition, &$sources): array {
                $base = [
                    'locale' => $value->locale,
                    'status' => $value->status->value,
                    'changed_at' => $value->changedAt?->toIso8601String(),
                ];

                // The sensitive-field gate (CLAUDE.md, ApiSensitiveGateTest).
                if ($definition->sensitive && $value->status !== FieldStatus::Verified) {
                    return $base + ['withheld' => true, 'value' => null, 'sources' => []];
                }

                foreach ($value->sources as $source) {
                    $sources[$source->id] = $this->source($source);
                }

                return $base + [
                    'withheld' => false,
                    'value' => $value->value,
                    'sources' => array_map(fn (CitedSource $source): int => $source->id, $value->sources),
                ];
            }, $slot->values),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function source(CitedSource $source): array
    {
        return [
            'id' => $source->id,
            'url' => $source->url,
            'publisher' => $source->publisher,
            'title' => $source->title,
            'license' => $source->license,
            'accessed_on' => $source->accessedOn,
            'archive_url' => $source->archiveUrl,
        ];
    }

    /**
     * @return list<int>
     */
    private function waves(): array
    {
        return array_values(array_filter(Config::array('atlas.publishing.page_waves'), is_int(...)));
    }
}
