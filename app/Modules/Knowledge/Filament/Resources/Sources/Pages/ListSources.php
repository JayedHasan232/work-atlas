<?php

namespace App\Modules\Knowledge\Filament\Resources\Sources\Pages;

use App\Modules\Knowledge\Filament\Resources\Sources\SourceResource;
use Filament\Resources\Pages\ListRecords;

final class ListSources extends ListRecords
{
    protected static string $resource = SourceResource::class;
}
