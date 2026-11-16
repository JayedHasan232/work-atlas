<?php

namespace App\Modules\Knowledge\Data;

/**
 * A new value for one slot: occupation × field definition × locale × region (D22: a null locale means
 * the same in every language, a null region means all of Bangladesh). The occupation and the field are
 * referenced by id, so other modules can build this without Knowledge's or Taxonomy's models.
 *
 * `value` is null or "unknown" when the value is not known (rule 2). `confidence` (0–1) is required
 * from an AI run and not allowed from a person.
 *
 * `secondOpinion` is for an AI run only: the value is kept as a proposal next to the current one (the
 * cross-check's second model, D50), and does not move the slot up the review queue on its own.
 *
 * From a person only: `declaredInterest` is what they declared about a conflict of interest (PLAN
 * M16), and `needsReview` keeps their write a proposal even when their role could apply it directly
 * (a new account adding links, Community's contribution limits).
 */
final readonly class RevisionInput
{
    /**
     * @param  list<int>  $sourceIds
     */
    public function __construct(
        public int $occupationId,
        public int $fieldDefinitionId,
        public mixed $value,
        public Actor $actor,
        public ?string $locale = null,
        public ?string $region = null,
        public array $sourceIds = [],
        public ?float $confidence = null,
        public ?string $comment = null,
        public bool $secondOpinion = false,
        public ?string $declaredInterest = null,
        public bool $needsReview = false,
    ) {}
}
