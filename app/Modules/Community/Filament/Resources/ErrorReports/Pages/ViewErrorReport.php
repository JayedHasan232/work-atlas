<?php

namespace App\Modules\Community\Filament\Resources\ErrorReports\Pages;

use App\Modules\Community\Filament\Resources\ErrorReports\ErrorReportResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewErrorReport extends ViewRecord
{
    protected static string $resource = ErrorReportResource::class;

    protected function getHeaderActions(): array
    {
        return ErrorReportResource::handleActions();
    }
}
