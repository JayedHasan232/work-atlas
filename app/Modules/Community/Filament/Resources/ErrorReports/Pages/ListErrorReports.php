<?php

namespace App\Modules\Community\Filament\Resources\ErrorReports\Pages;

use App\Modules\Community\Filament\Resources\ErrorReports\ErrorReportResource;
use Filament\Resources\Pages\ListRecords;

final class ListErrorReports extends ListRecords
{
    protected static string $resource = ErrorReportResource::class;
}
