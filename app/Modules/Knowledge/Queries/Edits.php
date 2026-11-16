<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Data\EditRecord;
use App\Modules\Knowledge\Data\SlotForReview;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\RevisionReview;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Revisions for the moderation tools (PLAN M18): people's recent edits for the patrol queue, one
 * edit with its context, a person's earlier edits for the promotional-edit checks, and a slot with
 * its waiting proposals for the review desk.
 */
final class Edits
{
    /**
     * People's edits since a time, newest first, in pages: pass the last revision id of a page as
     * `$beforeId` for the next one.
     *
     * @return list<EditRecord>
     */
    public function recent(CarbonInterface $since, ?int $beforeId = null, int $limit = 100): array
    {
        return $this->records($this->query()
            ->whereNotNull('user_id')
            ->where('created_at', '>=', $since)
            ->when($beforeId !== null, fn (Builder $query) => $query->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit($limit));
    }

    public function find(int $revisionId): ?EditRecord
    {
        return $this->records($this->query()->whereKey($revisionId))[0] ?? null;
    }

    /**
     * A person's other edits since a time, newest first.
     *
     * @return list<EditRecord>
     */
    public function byAuthor(int $userId, CarbonInterface $since, ?int $exceptRevisionId = null, int $limit = 500): array
    {
        return $this->records($this->query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->when($exceptRevisionId !== null, fn (Builder $query) => $query->whereKeyNot($exceptRevisionId))
            ->orderByDesc('id')
            ->limit($limit));
    }

    public function slot(int $fieldValueId): ?SlotForReview
    {
        $slot = FieldValue::query()->with(['definition', 'currentRevision:id,new_value'])->find($fieldValueId);

        if ($slot === null) {
            return null;
        }

        $proposals = $this->records($this->query()
            ->where('field_value_id', $slot->id)
            ->where('proposed', true)
            ->whereDoesntHave('reviews')
            ->orderBy('id'));

        return new SlotForReview(
            fieldValueId: $slot->id,
            occupationId: $slot->occupation_id,
            definition: $slot->definition->toData(),
            locale: $slot->locale,
            region: $slot->region,
            status: $slot->status,
            priority: $slot->review_priority,
            crossCheck: $slot->cross_check,
            currentRevisionId: $slot->current_revision_id,
            currentValue: $slot->currentRevision?->new_value,
            proposals: $proposals,
        );
    }

    /**
     * @return Builder<Revision>
     */
    private function query(): Builder
    {
        return Revision::query()->with([
            'fieldValue.definition',
            'sources:id,url,title',
            'reviews' => fn ($query) => $query->select(['id', 'revision_id', 'decision']),
        ]);
    }

    /**
     * @param  Builder<Revision>  $query
     * @return list<EditRecord>
     */
    private function records(Builder $query): array
    {
        return array_values($query->get()->map(function (Revision $revision): EditRecord {
            $slot = $revision->fieldValue;
            /** @var RevisionReview|null $review */
            $review = $revision->reviews->first();

            return new EditRecord(
                revisionId: $revision->id,
                fieldValueId: $slot->id,
                occupationId: $slot->occupation_id,
                definition: $slot->definition->toData(),
                locale: $slot->locale,
                region: $slot->region,
                authorUserId: $revision->user_id,
                proposed: $revision->proposed,
                review: $review?->decision,
                current: $slot->current_revision_id === $revision->id,
                revertsRevisionId: $revision->reverts_revision_id,
                oldValue: $revision->old_value,
                newValue: $revision->new_value,
                comment: $revision->comment,
                declaredInterest: $revision->declared_interest,
                sources: array_values($revision->sources->map(fn ($source): array => ['url' => $source->url, 'title' => $source->title])->all()),
                slotStatus: $slot->status,
                createdAt: $revision->created_at,
            );
        })->all());
    }
}
