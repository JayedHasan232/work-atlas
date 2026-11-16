<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Data\EditSuggestion;
use App\Modules\Community\Data\SuggestionResult;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Actions\RecordSource;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Data\SourceInput;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Queries\FieldDefinitionByKey;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use Illuminate\Support\Facades\DB;

/**
 * A person's suggested change (PLAN M16), written through Knowledge's RecordRevision like every other
 * change: a contributor's becomes a proposal, a trusted person's applies at once on a non-sensitive
 * field (D36). The Knowledge rules hold here too: a `requires_source` field needs a source, and the
 * value must fit the field. The new-account limits come first, and a new account's links wait for a
 * review. Names are changed through the list review, not here.
 *
 * Throws ContributionRejected, or a Knowledge rule violation, and writes nothing, when it fails.
 */
final class SuggestEdit
{
    public function __construct(
        private readonly PublicOccupations $occupations,
        private readonly FieldDefinitionByKey $fields,
        private readonly CheckContributionLimits $limits,
        private readonly RecordSource $recordSource,
        private readonly RecordRevision $recordRevision,
    ) {}

    public function handle(EditSuggestion $suggestion): SuggestionResult
    {
        $user = User::query()->findOrFail($suggestion->userId);

        if ($this->occupations->find($suggestion->occupationId) === null) {
            throw ContributionRejected::because('occupation', "occupation {$suggestion->occupationId} has no public page");
        }

        $field = $this->fields->latest($suggestion->fieldKey);

        if ($field === null || $field->definition->type === FieldType::NameList) {
            throw ContributionRejected::because('field', "field {$suggestion->fieldKey} cannot be suggested here");
        }

        $this->limits->handle($user);

        $comment = $this->text($suggestion->comment);
        $interest = $this->text($suggestion->declaredInterest);
        $needsReview = $this->limits->linksNeedReview($user, $suggestion->sources !== [], [$comment, $interest]);

        return DB::transaction(function () use ($suggestion, $field, $user, $comment, $interest, $needsReview): SuggestionResult {
            $sourceIds = array_values(array_unique(array_map(
                fn (SourceInput $source): int => $this->recordSource->handle($source),
                $suggestion->sources,
            )));

            $revision = $this->recordRevision->handle(new RevisionInput(
                occupationId: $suggestion->occupationId,
                fieldDefinitionId: $field->id,
                value: $suggestion->value,
                actor: Actor::user($user),
                locale: $field->definition->type->isPerLanguage() ? $suggestion->locale : null,
                sourceIds: $sourceIds,
                comment: $comment,
                declaredInterest: $interest,
                needsReview: $needsReview,
            ));

            return new SuggestionResult($revision->id, ! $revision->proposed);
        });
    }

    private function text(?string $text): ?string
    {
        $text = $text === null ? null : trim($text);

        return $text === '' ? null : $text;
    }
}
