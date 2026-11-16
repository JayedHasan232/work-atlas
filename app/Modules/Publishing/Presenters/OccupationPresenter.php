<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Community\Queries\Discussions;
use App\Modules\Community\Queries\PublicContributors;
use App\Modules\Knowledge\Data\FieldSlot;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Queries\ContributionCredits;
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
        private readonly FieldDisplay $display,
        private readonly ContributionCredits $credits,
        private readonly PublicContributors $contributors,
        private readonly Discussions $discussions,
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

            $field = $this->field($slot, $occupation->slug, $locale, $sources);
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
            'reportUrl' => LocalizedRoutes::path('occupations.report', ['slug' => $occupation->slug], $locale),
            'talkUrl' => LocalizedRoutes::path('talk.index', ['slug' => $occupation->slug], $locale),
            'talkCount' => $this->discussions->visibleThreadCount($occupation->id),
            'credits' => $this->credits($occupation->id, $locale),
        ];
    }

    /**
     * The people credited on this page (PLAN M16): named ones with a link to their profile, most changes
     * first, and how many chose to stay anonymous.
     *
     * @return array{people: list<array{name: string, url: string, contributions: int}>, anonymous: int}
     */
    private function credits(int $occupationId, string $locale): array
    {
        $credits = $this->credits->forOccupation($occupationId);
        $named = $this->contributors->named(array_map(fn ($credit): int => $credit->userId, $credits));
        $people = [];
        $anonymous = 0;

        foreach ($credits as $credit) {
            $contributor = $named[$credit->userId] ?? null;

            if ($contributor === null) {
                $anonymous++;

                continue;
            }

            $people[] = [
                'name' => $contributor->name,
                'url' => LocalizedRoutes::path('contributors.show', ['id' => $contributor->id], $locale),
                'contributions' => $credit->contributions,
            ];
        }

        return ['people' => $people, 'anonymous' => $anonymous];
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
    private function field(FieldSlot $slot, string $slug, string $locale, SourceNumbers $sources): array
    {
        $definition = $slot->definition;
        $value = $this->display->pick($slot->values, $locale);

        $field = [
            'key' => $definition->key,
            'suggestUrl' => LocalizedRoutes::path('occupations.suggest', ['slug' => $slug, 'field' => $definition->key], $locale),
            'reportUrl' => LocalizedRoutes::path('occupations.report', ['slug' => $slug], $locale).'?field='.rawurlencode($definition->key),
            'discussUrl' => LocalizedRoutes::path('talk.index', ['slug' => $slug], $locale).'?field='.rawurlencode($definition->key),
            'label' => $this->display->text($definition->label, $locale) ?? $definition->key,
            'help' => $this->display->text($definition->help, $locale),
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
            'value' => $this->display->display($definition, $value->value, $locale),
            'valueLocale' => $value->locale,
            'sources' => array_map(fn ($source): int => $sources->number($source), $value->sources),
        ] + $field;
    }
}
