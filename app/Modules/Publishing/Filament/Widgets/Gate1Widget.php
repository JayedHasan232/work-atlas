<?php

namespace App\Modules\Publishing\Filament\Widgets;

use App\Modules\Publishing\Queries\Gate1Progress;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Gate 1 → Phase 2 on the admin dashboard (PLAN M23, D80): listed occupations, Wave 1 values the
 * community supports, and people active in the last 30 days, inside and outside the core team.
 */
final class Gate1Widget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getHeading(): string
    {
        return __('admin.gate1.heading');
    }

    protected function getDescription(): string
    {
        return __('admin.gate1.description');
    }

    /** @return array<Stat> */
    protected function getStats(): array
    {
        $status = app(Gate1Progress::class)->handle();
        $wave1 = $status->wave1;

        return [
            Stat::make(__('admin.gate1.listed'), number_format($status->listedOccupations)),
            Stat::make(__('admin.gate1.wave1_supported'), __('admin.gate1.percent', ['percent' => $wave1->supportedPercent()]))
                ->description(__('admin.gate1.wave1_detail', [
                    'supported' => number_format($wave1->supported),
                    'filled' => number_format($wave1->filled),
                    'slots' => number_format($wave1->slots),
                ])),
            Stat::make(__('admin.gate1.active'), number_format($status->activeContributors->all))
                ->description(__('admin.gate1.active_detail', [
                    'outside' => number_format($status->activeContributors->outsideCoreTeam),
                    'days' => Gate1Progress::ACTIVE_DAYS,
                ])),
        ];
    }
}
