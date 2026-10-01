<?php

namespace App\Filament\Resources\Venues\Schemas;

use App\Enums\VenueType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VenueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('type')
                    ->options(VenueType::class)
                    ->default('club')
                    ->required(),
                TextInput::make('city'),
                TextInput::make('address'),
                TextInput::make('capacity')
                    ->numeric(),
                TextInput::make('website')
                    ->url(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
