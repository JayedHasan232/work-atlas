<?php

namespace App\Modules\Taxonomy\Actions;

use App\Models\User;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Queries\FieldDefinitionByKey;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Exceptions\ListReviewNotAllowed;
use App\Modules\Taxonomy\Exceptions\NamesFieldMissing;
use App\Modules\Taxonomy\Models\Occupation;

/**
 * A person changes an occupation's names in one language: a new revision of the `names` field (D44),
 * with the usual Knowledge rules (a contributor's write is a proposal). Not for a merged or rejected
 * occupation.
 */
final class UpdateOccupationNames
{
    public function __construct(
        private readonly RecordRevision $recordRevision,
        private readonly FieldDefinitionByKey $fieldDefinition,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $names
     */
    public function handle(int $occupationId, string $locale, array $names, User $user, ?string $comment = null): void
    {
        $state = Occupation::query()->findOrFail($occupationId)->publish_state;

        if (in_array($state, [PublishState::Merged, PublishState::Rejected], true)) {
            throw ListReviewNotAllowed::because("The names of a {$state->value} occupation are not edited.");
        }

        $this->recordRevision->handle(new RevisionInput(
            occupationId: $occupationId,
            fieldDefinitionId: $this->fieldDefinition->handle(SyncOccupationNames::FIELD_KEY) ?? throw NamesFieldMissing::make(),
            value: $names,
            actor: Actor::user($user),
            locale: $locale,
            comment: $comment,
        ));
    }
}
