<?php

namespace App\Filament\Resources\Unavailabilities;

use App\Filament\Resources\Unavailabilities\Pages\CreateUnavailability;
use App\Filament\Resources\Unavailabilities\Pages\EditUnavailability;
use App\Filament\Resources\Unavailabilities\Pages\ListUnavailabilities;
use App\Filament\Resources\Unavailabilities\Schemas\UnavailabilityForm;
use App\Filament\Resources\Unavailabilities\Tables\UnavailabilitiesTable;
use App\Models\Unavailability;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UnavailabilityResource extends Resource
{
    protected static ?string $model = Unavailability::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UnavailabilityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnavailabilitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnavailabilities::route('/'),
            'create' => CreateUnavailability::route('/create'),
            'edit' => EditUnavailability::route('/{record}/edit'),
        ];
    }
}
