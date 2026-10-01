<?php

namespace App\Filament\Widgets;

use App\Enums\GigStatus;
use App\Models\Gig;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingGigs extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Prochaines dates')
            ->query(
                Gig::query()
                    ->where('status', GigStatus::Confirmed)
                    ->whereDate('date', '>=', today())
                    ->with('venue')
            )
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')->date('d/m/Y')->sortable(),
                TextColumn::make('venue.name')->label('Lieu'),
                TextColumn::make('venue.city')->label('Ville'),
                TextColumn::make('set_time')->label('Set')->time('H:i')->placeholder('—'),
                TextColumn::make('fee')->label('Cachet')->money('EUR')->placeholder('—'),
                IconColumn::make('accommodation')->label('Hébergé')->boolean(),
            ])
            ->emptyStateHeading('Aucune date confirmée');
    }
}
