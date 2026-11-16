<?php

namespace App\Modules\AiPipeline\Filament\Resources\AiRuns\Pages;

use App\Modules\AiPipeline\Filament\Resources\AiRuns\AiRunResource;
use Filament\Resources\Pages\ListRecords;

final class ListAiRuns extends ListRecords
{
    protected static string $resource = AiRunResource::class;
}
