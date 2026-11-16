<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Knowledge\Data\FieldSlot;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Queries\FieldDefinitionByKey;
use App\Modules\Knowledge\Queries\OccupationFields;
use App\Modules\Taxonomy\Data\OccupationListItem;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Support\Facades\Config;

/**
 * The forms behind the occupation page (PLAN M16): reporting an error, and suggesting a change to one
 * field. The suggestion form gets the field's input shape (scale anchors, options) and the current value
 * for the before-and-after preview; the sensitive-field gate holds here too, so an unverified sensitive
 * value is not sent.
 */
final class ContributionFormPresenter
{
    public function __construct(
        private readonly OccupationFields $fields,
        private readonly FieldDefinitionByKey $definitions,
        private readonly OccupationLinks $links,
        private readonly FieldDisplay $display,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(OccupationListItem $occupation, string $locale, ?string $fieldKey): array
    {
        $fields = [];

        foreach ($this->slots($occupation->id) as $slot) {
            $fields[] = ['key' => $slot->definition->key, 'label' => $this->label($slot, $locale)];
        }

        return [
            'occupation' => $this->links->link($occupation, $locale),
            'fields' => $fields,
            'field' => in_array($fieldKey, array_column($fields, 'key'), true) ? $fieldKey : null,
            'submitUrl' => LocalizedRoutes::path('occupations.report.store', ['slug' => $occupation->slug], $locale),
        ];
    }

    /**
     * The suggestion form for one field, or null when the field cannot be suggested here.
     *
     * @return array<string, mixed>|null
     */
    public function suggest(OccupationListItem $occupation, string $fieldKey, string $locale): ?array
    {
        $field = $this->definitions->latest($fieldKey);
        $slot = null;

        foreach ($this->slots($occupation->id) as $candidate) {
            if ($candidate->definition->key === $fieldKey) {
                $slot = $candidate;
            }
        }

        if ($field === null || $slot === null) {
            return null;
        }

        $definition = $field->definition;
        $current = $this->display->pick($slot->values, $locale);
        $withheld = $current !== null && $definition->sensitive && $current->status !== FieldStatus::Verified;

        return [
            'occupation' => $this->links->link($occupation, $locale),
            'field' => [
                'key' => $definition->key,
                'label' => $this->display->text($definition->label, $locale) ?? $definition->key,
                'help' => $this->display->text($definition->help, $locale),
                'type' => $definition->type->value,
                'options' => array_map(fn (string $option): array => [
                    'value' => $option,
                    'label' => $this->display->text($definition->optionLabels[$option] ?? [], $locale) ?? $option,
                ], $definition->options ?? []),
                'anchors' => array_map(fn (int $point): array => [
                    'point' => $point,
                    'label' => $this->display->text(array_map(
                        fn (array $points): string => $points[$point] ?? '',
                        $definition->anchors ?? [],
                    ), $locale),
                ], range(1, 5)),
                'requiresSource' => $definition->requiresSource,
                'sensitive' => $definition->sensitive,
            ],
            'current' => $current === null ? null : [
                'status' => $current->status->value,
                'withheld' => $withheld,
                'value' => $withheld ? null : $this->display->display($definition, $current->value, $locale),
            ],
            'submitUrl' => LocalizedRoutes::path('occupations.suggest.store', ['slug' => $occupation->slug, 'field' => $fieldKey], $locale),
            'pageUrl' => LocalizedRoutes::path('occupations.show', ['slug' => $occupation->slug], $locale),
        ];
    }

    /**
     * The fields shown on the page, without the names (changed through the list review).
     *
     * @return list<FieldSlot>
     */
    private function slots(int $occupationId): array
    {
        /** @var list<int> $waves */
        $waves = Config::array('atlas.publishing.page_waves');

        return array_values(array_filter(
            $this->fields->handle($occupationId, $waves),
            fn (FieldSlot $slot): bool => $slot->definition->type !== FieldType::NameList,
        ));
    }

    private function label(FieldSlot $slot, string $locale): string
    {
        return $this->display->text($slot->definition->label, $locale) ?? $slot->definition->key;
    }
}
