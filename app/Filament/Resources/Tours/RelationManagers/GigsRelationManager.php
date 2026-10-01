<?php

namespace App\Filament\Resources\Tours\RelationManagers;

use App\Filament\Resources\Gigs\GigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class GigsRelationManager extends RelationManager
{
    protected static string $relationship = 'gigs';

    protected static ?string $relatedResource = GigResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
