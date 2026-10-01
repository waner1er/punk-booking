<?php

namespace App\Filament\Resources\Gigs\RelationManagers;

use App\Enums\InteractionType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    protected static ?string $title = 'Échanges';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('Type')
                ->options(InteractionType::class)
                ->default(InteractionType::Email)
                ->required(),
            DateTimePicker::make('happened_at')
                ->label('Quand')
                ->default(now())
                ->required(),
            Select::make('contact_id')
                ->label('Contact')
                ->relationship('contact', 'name')
                ->default(fn () => $this->getOwnerRecord()->contact_id)
                ->searchable()
                ->preload(),
            Textarea::make('summary')
                ->label('Résumé')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('summary')
            ->defaultSort('happened_at', 'desc')
            ->columns([
                TextColumn::make('happened_at')->label('Quand')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('contact.name')->label('Contact')->placeholder('—'),
                TextColumn::make('summary')->label('Résumé')->limit(80)->wrap(),
            ])
            ->headerActions([
                CreateAction::make()->label('Noter un échange'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
