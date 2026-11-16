<?php

namespace App\Modules\Taxonomy\Filament\Resources\Occupations\Pages;

use App\Modules\Taxonomy\Filament\Resources\Occupations\OccupationResource;
use Filament\Resources\Pages\ListRecords;

final class ListOccupations extends ListRecords
{
    protected static string $resource = OccupationResource::class;
}
