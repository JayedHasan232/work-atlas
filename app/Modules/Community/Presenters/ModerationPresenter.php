<?php

namespace App\Modules\Community\Presenters;

use App\Models\User;
use App\Modules\Community\Data\PatrolEntry;
use App\Modules\Community\Queries\PatrolQueue;
use App\Modules\Community\Queries\PeopleForModeration;
use App\Modules\Knowledge\Data\EditRecord;
use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Queries\Edits;
use App\Modules\Knowledge\Queries\FieldDefinitionByKey;
use App\Modules\Knowledge\Queries\ReviewQueue;
use App\Modules\Knowledge\Queries\RevisionDiff;
use App\Modules\Publishing\Presenters\FieldDisplay;
use App\Modules\Publishing\Presenters\OccupationLinks;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use App\Support\Localization\LocalizedRoutes;

/**
 * The moderation pages (PLAN M18, D66) for reviewers and above: the review queue, one value with its
 * proposals, the patrol queue and one edit. Not a public read path: reviewers see sensitive values
 * that are not verified yet, so every page is behind the `moderate` gate and `noindex`.
 */
final class ModerationPresenter
{
    public function __construct(
        private readonly ReviewQueue $reviewQueue,
        private readonly PatrolQueue $patrolQueue,
        private readonly Edits $edits,
        private readonly RevisionDiff $diff,
        private readonly PeopleForModeration $people,
        private readonly PublicOccupations $occupations,
        private readonly OccupationLinks $links,
        private readonly FieldDisplay $display,
        private readonly FieldDefinitionByKey $definitions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function reviewQueue(string $locale): array
    {
        $items = $this->reviewQueue->handle(limit: 100);
        $occupations = $this->occupationLinks(array_map(fn ($item): int => $item->occupationId, $items), $locale);
        $labels = [];

        foreach ($items as $item) {
            if (! isset($labels[$item->fieldKey])) {
                $field = $this->definitions->latest($item->fieldKey);
                $labels[$item->fieldKey] = ($field === null ? null : $this->display->text($field->definition->label, $locale)) ?? $item->fieldKey;
            }
        }

        return ['items' => array_map(fn ($item): array => [
            'fieldValueId' => $item->fieldValueId,
            'occupation' => $occupations[$item->occupationId] ?? null,
            'field' => $labels[$item->fieldKey],
            'locale' => $item->locale,
            'status' => $item->status->value,
            'reason' => ReviewQueue::reason($item->priority),
            'proposals' => count($item->proposedValues),
            'url' => LocalizedRoutes::path('review.show', ['slot' => $item->fieldValueId], $locale),
        ], $items)];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function reviewSlot(int $fieldValueId, User $user, string $locale): ?array
    {
        $slot = $this->edits->slot($fieldValueId);

        if ($slot === null) {
            return null;
        }

        $people = $this->people->handle(array_values(array_filter(array_map(fn (EditRecord $edit): ?int => $edit->authorUserId, $slot->proposals))));
        $definition = $slot->definition;

        return [
            'fieldValueId' => $slot->fieldValueId,
            'occupation' => $this->occupationLinks([$slot->occupationId], $locale)[$slot->occupationId] ?? null,
            'field' => $this->field($definition, $locale),
            'locale' => $slot->locale,
            'status' => $slot->status->value,
            'reason' => ReviewQueue::reason($slot->priority),
            'crossCheck' => $slot->crossCheck?->value,
            'current' => $this->display->display($definition, $slot->currentValue, $locale),
            'proposals' => array_map(fn (EditRecord $edit): array => [
                ...$this->edit($edit, $people, $locale),
                'reviewUrl' => LocalizedRoutes::path('review.decide', ['slot' => $slot->fieldValueId, 'revision' => $edit->revisionId], $locale),
            ], $slot->proposals),
            'statuses' => array_values(array_map(
                fn (FieldStatus $status): string => $status->value,
                array_filter(FieldStatus::cases(), fn (FieldStatus $status): bool => $status !== $slot->status && $user->role->canSetStatus($status, $definition->sensitive)),
            )),
            'statusUrl' => LocalizedRoutes::path('review.status', ['slot' => $slot->fieldValueId], $locale),
            'historyUrl' => $slot->currentRevisionId === null ? null : LocalizedRoutes::path('patrol.show', ['revision' => $slot->currentRevisionId], $locale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function patrolQueue(string $locale): array
    {
        $entries = $this->patrolQueue->handle();
        $people = $this->people->handle(array_values(array_filter(array_map(fn (PatrolEntry $entry): ?int => $entry->edit->authorUserId, $entries))));
        $occupations = $this->occupationLinks(array_map(fn (PatrolEntry $entry): int => $entry->edit->occupationId, $entries), $locale);

        return ['entries' => array_map(fn (PatrolEntry $entry): array => [
            'revisionId' => $entry->edit->revisionId,
            'occupation' => $occupations[$entry->edit->occupationId] ?? null,
            'field' => $this->display->text($entry->edit->definition->label, $locale) ?? $entry->edit->definition->key,
            'author' => $entry->edit->authorUserId === null ? null : ($people[$entry->edit->authorUserId] ?? null),
            'kind' => $this->kind($entry->edit),
            'flags' => $this->flags($entry),
            'createdAt' => $entry->edit->createdAt?->toIso8601String(),
            'url' => LocalizedRoutes::path('patrol.show', ['revision' => $entry->edit->revisionId], $locale),
        ], $entries)];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function patrolEdit(int $revisionId, User $user, string $locale): ?array
    {
        $entry = $this->patrolQueue->find($revisionId);

        if ($entry === null) {
            return null;
        }

        $edit = $entry->edit;
        $people = $edit->authorUserId === null ? [] : $this->people->handle([$edit->authorUserId]);

        return [
            ...$this->edit($edit, $people, $locale),
            'occupation' => $this->occupationLinks([$edit->occupationId], $locale)[$edit->occupationId] ?? null,
            'field' => $this->field($edit->definition, $locale),
            'locale' => $edit->locale,
            'old' => $this->display->display($edit->definition, $edit->oldValue, $locale),
            'status' => $edit->slotStatus->value,
            'current' => $edit->current,
            'canRevert' => $edit->current && $user->role->canRevert($edit->definition->sensitive, $edit->slotStatus),
            'patrolled' => $entry->patrolled,
            'flags' => $this->flags($entry),
            'revertUrl' => LocalizedRoutes::path('patrol.revert', ['revision' => $edit->revisionId], $locale),
            'patrolUrl' => LocalizedRoutes::path('patrol.mark', ['revision' => $edit->revisionId], $locale),
            'slotUrl' => LocalizedRoutes::path('review.show', ['slot' => $edit->fieldValueId], $locale),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $people
     * @return array<string, mixed>
     */
    private function edit(EditRecord $edit, array $people, string $locale): array
    {
        return [
            'revisionId' => $edit->revisionId,
            'value' => $this->display->display($edit->definition, $edit->newValue, $locale),
            'diff' => $this->diff->handle($edit->revisionId),
            'author' => $edit->authorUserId === null ? null : ($people[$edit->authorUserId] ?? null),
            'kind' => $this->kind($edit),
            'comment' => $edit->comment,
            'declaredInterest' => $edit->declaredInterest,
            'sources' => $edit->sources,
            'createdAt' => $edit->createdAt?->toIso8601String(),
        ];
    }

    /**
     * @return array{key: string, label: string, help: string|null, sensitive: bool}
     */
    private function field(FieldDefinitionData $definition, string $locale): array
    {
        return [
            'key' => $definition->key,
            'label' => $this->display->text($definition->label, $locale) ?? $definition->key,
            'help' => $this->display->text($definition->help, $locale),
            'sensitive' => $definition->sensitive,
        ];
    }

    /**
     * @return list<array{reason: string, detail: string|null}>
     */
    private function flags(PatrolEntry $entry): array
    {
        return array_map(fn (array $flag): array => ['reason' => $flag['reason']->value, 'detail' => $flag['detail']], $entry->flags);
    }

    private function kind(EditRecord $edit): string
    {
        return match (true) {
            $edit->revertsRevisionId !== null => 'revert',
            $edit->proposed && $edit->review === null => 'proposal',
            $edit->proposed => $edit->review->value,
            default => 'applied',
        };
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, array<string, string>>
     */
    private function occupationLinks(array $ids, string $locale): array
    {
        $links = [];

        foreach ($this->occupations->findMany(array_values(array_unique($ids))) as $item) {
            $links[$item->id] = $this->links->link($item, $locale);
        }

        return $links;
    }
}
