<?php

namespace App\Modules\Taxonomy\Actions;

use App\Models\User;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Queries\FieldDefinitionByKey;
use App\Modules\Taxonomy\Enums\DuplicateStatus;
use App\Modules\Taxonomy\Enums\NameType;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Exceptions\ListReviewNotAllowed;
use App\Modules\Taxonomy\Exceptions\NamesFieldMissing;
use App\Modules\Taxonomy\Models\DuplicateCandidate;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;
use Illuminate\Support\Facades\DB;

/**
 * Merges one occupation into another (PLAN M11, D5, D48), also for "this is a specialization of …".
 * In one transaction:
 *
 * - the merged occupation's names are added to the target's `names` value in each language through a
 *   revision by the reviewer: its primary name becomes an alias, local and regional names keep their
 *   type, and a name the target already has is not added twice;
 * - the merged occupation becomes `merged` with `merged_into_id`, so its slug redirects (301);
 *   occupations merged into it earlier now point at the target, so a redirect is one step;
 * - its search names are dropped (they now belong to the target), the pair is marked merged and its
 *   other open pairs are dropped.
 *
 * Its own field values and revisions stay as history. Reviewers and above.
 */
final class MergeOccupations
{
    public function __construct(
        private readonly RecordRevision $recordRevision,
        private readonly FieldDefinitionByKey $fieldDefinition,
        private readonly NormalizeName $normalize,
    ) {}

    public function handle(int $fromId, int $intoId, User $user, ?string $note = null): void
    {
        if (! $user->role->canReview()) {
            throw ListReviewNotAllowed::because('Only reviewers and above can review the occupation list.');
        }

        if ($fromId === $intoId) {
            throw ListReviewNotAllowed::because('An occupation cannot be merged into itself.');
        }

        $namesField = $this->fieldDefinition->handle(SyncOccupationNames::FIELD_KEY) ?? throw NamesFieldMissing::make();

        DB::transaction(function () use ($fromId, $intoId, $user, $note, $namesField): void {
            $locked = Occupation::query()->whereKey([$fromId, $intoId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $from = $locked->get($fromId) ?? throw ListReviewNotAllowed::because("There is no occupation {$fromId}.");
            $into = $locked->get($intoId) ?? throw ListReviewNotAllowed::because("There is no occupation {$intoId}.");

            foreach ([$from, $into] as $occupation) {
                if (in_array($occupation->publish_state, [PublishState::Merged, PublishState::Rejected], true)) {
                    throw ListReviewNotAllowed::because("{$occupation->slug} is {$occupation->publish_state->value} and cannot take part in a merge.");
                }
            }

            foreach (['bn', 'en'] as $locale) {
                $this->moveNames($from, $into, $locale, $namesField, $user, $note);
            }

            Occupation::query()->where('merged_into_id', $from->id)->update(['merged_into_id' => $into->id]);

            $from->update([
                'publish_state' => PublishState::Merged,
                'merged_into_id' => $into->id,
                'indexable' => false,
                'reviewed_by_id' => $user->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            OccupationName::query()->where('occupation_id', $from->id)->delete();

            $this->closePairs($from->id, $into->id, $user, $note);
        });
    }

    private function moveNames(Occupation $from, Occupation $into, string $locale, int $namesField, User $user, ?string $note): void
    {
        $incoming = $this->names($from->id, $locale);

        if ($incoming === []) {
            return;
        }

        $current = $this->names($into->id, $locale);
        $merged = $current === [] ? $incoming : $current;

        if ($current !== []) {
            $seen = array_map(fn (array $name): string => $this->normalize->handle($name['name']), $current);

            foreach ($incoming as $name) {
                $key = $this->normalize->handle($name['name']);

                if (! in_array($key, $seen, true)) {
                    $merged[] = $name['type'] === NameType::Primary->value ? ['name' => $name['name'], 'type' => NameType::Alias->value] : $name;
                    $seen[] = $key;
                }
            }
        }

        if ($merged === $current) {
            return;
        }

        $revision = $this->recordRevision->handle(new RevisionInput(
            occupationId: $into->id,
            fieldDefinitionId: $namesField,
            value: $merged,
            actor: Actor::user($user),
            locale: $locale,
            comment: trim("Names of the merged occupation {$from->slug}. ".($note ?? '')),
        ));

        if ($revision->proposed) {
            throw ListReviewNotAllowed::because('The names could not be moved directly, so nothing was merged.');
        }
    }

    /**
     * The occupation's current names in one language, in their order (the search copy of the names
     * field).
     *
     * @return list<array{name: string, type: string, region?: string}>
     */
    private function names(int $occupationId, string $locale): array
    {
        return array_values(OccupationName::query()
            ->where('occupation_id', $occupationId)
            ->where('locale', $locale)
            ->orderBy('id')
            ->get()
            ->map(fn (OccupationName $name): array => array_filter([
                'name' => $name->name,
                'type' => $name->type->value,
                'region' => $name->region,
            ], fn (?string $value): bool => $value !== null))
            ->all());
    }

    private function closePairs(int $fromId, int $intoId, User $user, ?string $note): void
    {
        [$low, $high] = $fromId < $intoId ? [$fromId, $intoId] : [$intoId, $fromId];

        DuplicateCandidate::query()
            ->where('occupation_id', $low)->where('other_occupation_id', $high)->where('status', DuplicateStatus::Open)
            ->update(['status' => DuplicateStatus::Merged, 'decided_by_id' => $user->id, 'decided_at' => now(), 'decision_note' => $note]);

        DuplicateCandidate::query()
            ->where('status', DuplicateStatus::Open)
            ->where(fn ($query) => $query->where('occupation_id', $fromId)->orWhere('other_occupation_id', $fromId))
            ->delete();
    }
}
