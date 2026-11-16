<?php

namespace App\Modules\Community\Actions;

use App\Modules\Community\Data\ErrorReportInput;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Community\Models\ErrorReport;
use App\Modules\Knowledge\Queries\FieldDefinitionByKey;
use App\Modules\Taxonomy\Queries\PublicOccupations;

/**
 * Stores a reader's error report in the moderation queue (PLAN M16). Anyone may report, signed in or
 * not; the spam checks (honeypot, rate limit) run before this, in the web layer. Only an occupation
 * with a public page can be reported, and the field, when given, must exist. Returns the report id.
 */
final class SubmitErrorReport
{
    public const int MAX_LENGTH = 2000;

    public function __construct(
        private readonly PublicOccupations $occupations,
        private readonly FieldDefinitionByKey $fields,
    ) {}

    public function handle(ErrorReportInput $input): int
    {
        $message = trim($input->message);

        if ($message === '' || mb_strlen($message) > self::MAX_LENGTH) {
            throw ContributionRejected::because('message', 'the message must be 1 to '.self::MAX_LENGTH.' characters');
        }

        if ($this->occupations->find($input->occupationId) === null) {
            throw ContributionRejected::because('occupation', "occupation {$input->occupationId} has no public page");
        }

        $fieldId = null;

        if ($input->fieldKey !== null) {
            $fieldId = $this->fields->handle($input->fieldKey)
                ?? throw ContributionRejected::because('field', "there is no field {$input->fieldKey}");
        }

        return ErrorReport::query()->create([
            'occupation_id' => $input->occupationId,
            'field_definition_id' => $fieldId,
            'locale' => $input->locale,
            'message' => $message,
            'user_id' => $input->userId,
        ])->id;
    }
}
