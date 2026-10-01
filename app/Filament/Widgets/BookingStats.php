<?php

namespace App\Filament\Widgets;

use App\Enums\GigStatus;
use App\Models\Gig;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make(
                'Dates confirmées à venir',
                Gig::where('status', GigStatus::Confirmed)->whereDate('date', '>=', today())->count(),
            )->color('success'),

            Stat::make('Pistes en cours', Gig::open()->count())->color('info'),

            Stat::make('À relancer', Gig::toFollowUp()->count())->color('warning'),
        ];
    }
}
