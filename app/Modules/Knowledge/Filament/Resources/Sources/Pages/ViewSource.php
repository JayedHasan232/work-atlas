<?php

namespace App\Modules\Knowledge\Filament\Resources\Sources\Pages;

use App\Modules\Knowledge\Filament\Resources\Sources\SourceResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewSource extends ViewRecord
{
    protected static string $resource = SourceResource::class;
}
