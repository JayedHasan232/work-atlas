<?php

namespace App\Modules\Taxonomy\Filament\Resources\DuplicateCandidates\Pages;

use App\Modules\Taxonomy\Filament\Resources\DuplicateCandidates\DuplicateCandidateResource;
use Filament\Resources\Pages\ListRecords;

final class ListDuplicateCandidates extends ListRecords
{
    protected static string $resource = DuplicateCandidateResource::class;
}
