<?php

namespace App\Modules\Taxonomy\Filament\Resources\DuplicateCandidates\Pages;

use App\Modules\Taxonomy\Filament\Resources\DuplicateCandidates\DuplicateCandidateResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewDuplicateCandidate extends ViewRecord
{
    protected static string $resource = DuplicateCandidateResource::class;

    protected function getHeaderActions(): array
    {
        return DuplicateCandidateResource::decisionActions();
    }
}
