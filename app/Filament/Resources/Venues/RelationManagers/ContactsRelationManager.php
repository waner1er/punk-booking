<?php

namespace App\Filament\Resources\Venues\RelationManagers;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $relatedResource = ContactResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                AttachAction::make()->preloadRecordSelect(),
                CreateAction::make(),
            ])
            ->recordActions([
                DetachAction::make(),
            ]);
    }
}
