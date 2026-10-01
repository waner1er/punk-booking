<?php

namespace App\Filament\Widgets;

use App\Models\Gig;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class GigsToFollowUp extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('À relancer')
            ->query(Gig::query()->toFollowUp()->with(['venue', 'contact']))
            ->defaultSort('next_follow_up_at')
            ->columns([
                TextColumn::make('next_follow_up_at')->label('Relance')->date('d/m/Y')->sortable(),
                TextColumn::make('venue.name')->label('Lieu'),
                TextColumn::make('venue.city')->label('Ville'),
                TextColumn::make('contact.name')->label('Contact')->placeholder('—'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('date')->label('Date visée')->date('d/m/Y')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('postpone')
                    ->label('+15 jours')
                    ->icon('heroicon-o-clock')
                    ->action(fn (Gig $record) => $record->postponeFollowUp(15)),
            ])
            ->emptyStateHeading('Rien à relancer');
    }
}
