<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Enums\ErrorReportState;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Community\Models\ErrorReport;

/**
 * A reviewer or maintainer closes an open error report (PLAN M16): resolved, dismissed or spam. The
 * report keeps who handled it and when.
 */
final class HandleErrorReport
{
    public function handle(int $reportId, int $handlerId, ErrorReportState $state): void
    {
        if ($state === ErrorReportState::Open) {
            throw ContributionRejected::because('state', 'a report is handled by closing it');
        }

        $handler = User::query()->findOrFail($handlerId);

        if (! $handler->role->canReview()) {
            throw ContributionRejected::because('role', "a {$handler->role->value} cannot handle reports");
        }

        $report = ErrorReport::query()->findOrFail($reportId);
        $report->state = $state;
        $report->handled_by = $handler->id;
        $report->handled_at = now();
        $report->save();
    }
}
