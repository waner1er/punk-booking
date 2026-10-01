<?php

namespace App\Filament\Resources\Gigs\Schemas;

use App\Enums\DealType;
use App\Enums\GigStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('venue_id')
                    ->relationship('venue', 'name')
                    ->required(),
                Select::make('contact_id')
                    ->relationship('contact', 'name'),
                Select::make('tour_id')
                    ->relationship('tour', 'name'),
                DatePicker::make('date'),
                TimePicker::make('load_in_at'),
                TimePicker::make('set_time'),
                TextInput::make('set_duration')
                    ->numeric(),
                Select::make('status')
                    ->options(GigStatus::class)
                    ->default('prospect')
                    ->required(),
                Select::make('deal_type')
                    ->options(DealType::class),
                TextInput::make('fee')
                    ->numeric(),
                TextInput::make('travel_costs')
                    ->numeric(),
                Toggle::make('accommodation')
                    ->required(),
                Toggle::make('meals')
                    ->required(),
                DatePicker::make('next_follow_up_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
