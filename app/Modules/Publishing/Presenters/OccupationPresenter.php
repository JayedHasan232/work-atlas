<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Data\FieldSlot;
use App\Modules\Knowledge\Data\SlotValue;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Queries\OccupationFields;
use App\Modules\Taxonomy\Data\OccupationListItem;
use App\Modules\Taxonomy\Queries\IscoGroups;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Config;

/**
 * The public occupation page (PLAN M13), and later the API: the only public read path for field
 * values. The sensitive-field gate lives here: a sensitive value (income, health, legal) that is not
 * `verified` leaves out its value and sources, so neither reaches the props, the HTML or the API.
 */
final class OccupationPresenter
{
    public function __construct(
        private readonly OccupationFields $fields,
        private readonly IscoGroups $groups,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(OccupationListItem $occupation, string $locale): array
    {
        /** @var list<int> $waves */
        $waves = Config::array('atlas.publishing.page_waves');
        /** @var list<string> $medicalGroups */
        $medicalGroups = Config::array('atlas.publishing.medical_groups');

        $sources = new SourceNumbers;
        $names = [];
        $sections = [];
        $changedAt = null;
        $unverified = false;

        foreach ($this->fields->handle($occupation->id, $waves) as $slot) {
            foreach ($slot->values as $value) {
                if ($changedAt === null || ($value->changedAt !== null && $value->changedAt->gt($changedAt))) {
                    $changedAt = $value->changedAt;
                }
            }

            if ($slot->definition->type === FieldType::NameList) {
                $names = $this->names($slot, $locale);

                continue;
            }

            $field = $this->field($slot, $locale, $sources);
            $unverified = $unverified || ($field['status'] !== null && $field['status'] !== FieldStatus::Verified->value);
            $group = $slot->definition->group;

            $sections[$group] ??= [
                'key' => $group,
                'title' => __("occupations.groups.{$group}"),
                'medical' => in_array($group, $medicalGroups, true),
                'fields' => [],
            ];
            $sections[$group]['fields'][] = $field;
        }

        [$name, $nameLocale] = $this->primaryName($occupation, $names, $locale);

        return [
            'slug' => $occupation->slug,
            'iscoCode' => $occupation->iscoCode,
            'name' => $name,
            'nameLocale' => $nameLocale,
            'names' => $names,
            'groups' => array_map(fn ($group): array => [
                'code' => $group->code,
                'title' => $group->title($locale),
                'titleLocale' => $group->titleLocale($locale),
                'url' => LocalizedRoutes::path('occupations.groups.show', ['code' => $group->code], $locale),
            ], $this->groups->path($occupation->iscoCode)),
            'sections' => array_values($sections),
            'sources' => $sources->all(),
            'unverified' => $unverified,
            'changedAt' => $changedAt?->toIso8601String(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $names
     * @return array{0: string, 1: string}
     */
    private function primaryName(OccupationListItem $occupation, array $names, string $locale): array
    {
        foreach ([$locale, ...array_keys($occupation->names)] as $candidate) {
            foreach ($names as $list) {
                if ($list['locale'] !== $candidate) {
                    continue;
                }

                foreach ($list['items'] as $item) {
                    if ($item['type'] === 'primary') {
                        return [$item['name'], $candidate];
                    }
                }
            }

            if (isset($occupation->names[$candidate])) {
                return [$occupation->names[$candidate], $candidate];
            }
        }

        return [$occupation->slug, 'en'];
    }

    /**
     * The names in each language, the reader's language first.
     *
     * @return list<array<string, mixed>>
     */
    private function names(FieldSlot $slot, string $locale): array
    {
        $lists = [];

        foreach ($slot->values as $value) {
            if (! is_array($value->value)) {
                continue;
            }

            $items = [];

            foreach ($value->value as $entry) {
                if (! is_array($entry) || ! is_string($entry['name'] ?? null)) {
                    continue;
                }

                $region = is_string($entry['region'] ?? null) ? $entry['region'] : null;
                $items[] = [
                    'name' => $entry['name'],
                    'type' => is_string($entry['type'] ?? null) ? $entry['type'] : 'alias',
                    'region' => $region === null ? null : __("admin.divisions.{$region}"),
                ];
            }

            $lists[] = [
                'locale' => $value->locale ?? $locale,
                'status' => $value->status->value,
                'items' => $items,
            ];
        }

        usort($lists, fn (array $a, array $b): int => ($b['locale'] === $locale) <=> ($a['locale'] === $locale));

        return $lists;
    }

    /**
     * @return array<string, mixed>
     */
    private function field(FieldSlot $slot, string $locale, SourceNumbers $sources): array
    {
        $definition = $slot->definition;
        $value = $this->pick($slot->values, $locale);

        $field = [
            'key' => $definition->key,
            'label' => $this->text($definition->label, $locale) ?? $definition->key,
            'help' => $this->text($definition->help, $locale),
            'status' => $value?->status->value,
            'withheld' => false,
            'value' => null,
            'valueLocale' => null,
            'sources' => [],
        ];

        if ($value === null) {
            return $field;
        }

        // The sensitive-field gate (CLAUDE.md, SensitiveFieldGateTest).
        if ($definition->sensitive && $value->status !== FieldStatus::Verified) {
            return ['withheld' => true] + $field;
        }

        return [
            'value' => $this->display($definition, $value->value, $locale),
            'valueLocale' => $value->locale,
            'sources' => array_map(fn ($source): int => $sources->number($source), $value->sources),
        ] + $field;
    }

    /**
     * The value in the reader's language, else the one without a language, else any.
     *
     * @param  list<SlotValue>  $values
     */
    private function pick(array $values, string $locale): ?SlotValue
    {
        foreach ([$locale, null] as $wanted) {
            foreach ($values as $value) {
                if ($value->locale === $wanted) {
                    return $value;
                }
            }
        }

        return $values[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function display(FieldDefinitionData $definition, mixed $value, string $locale): array
    {
        if ($value === null) {
            return ['kind' => 'unknown'];
        }

        return match ($definition->type) {
            FieldType::Scale1To5 => [
                'kind' => 'scale',
                'point' => (int) $value,
                'anchor' => $this->text(array_map(
                    fn (array $points): string => $points[(int) $value] ?? '',
                    $definition->anchors ?? [],
                ), $locale),
            ],
            FieldType::Enum, FieldType::Band, FieldType::MultiEnum => [
                'kind' => 'options',
                'labels' => array_map(
                    fn (mixed $option): string => $this->text($definition->optionLabels[(string) $option] ?? [], $locale) ?? (string) $option,
                    is_array($value) ? array_values($value) : [$value],
                ),
            ],
            FieldType::Boolean => ['kind' => 'boolean', 'value' => (bool) $value],
            default => ['kind' => 'text', 'text' => is_scalar($value) ? (string) $value : ''],
        };
    }

    /**
     * @param  array<string, string>  $texts  by locale
     */
    private function text(array $texts, string $locale): ?string
    {
        $text = $texts[$locale] ?? $texts[Config::string('app.fallback_locale')] ?? null;

        return $text === null || $text === '' ? null : $text;
    }
}
