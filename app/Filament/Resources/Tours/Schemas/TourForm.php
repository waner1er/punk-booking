<?php

namespace App\Filament\Resources\Tours\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TourForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                DatePicker::make('starts_at'),
                DatePicker::make('ends_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
