<?php

namespace App\Modules\Knowledge\Filament\Resources\CrossChecks\Pages;

use App\Modules\Knowledge\Filament\Resources\CrossChecks\CrossCheckResource;
use App\Modules\Knowledge\Queries\DisagreementReport;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ListCrossChecks extends ListRecords
{
    protected static string $resource = CrossCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('admin.cross_checks.export'))
                ->action(fn (DisagreementReport $report): StreamedResponse => response()->streamDownload(function () use ($report): void {
                    $out = fopen('php://output', 'w');

                    if ($out === false) {
                        return;
                    }

                    foreach ($report->handle() as $row) {
                        fputcsv($out, $row, escape: '');
                    }

                    fclose($out);
                }, 'wave1-disagreements-'.now('Asia/Dhaka')->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8'])),
        ];
    }
}
