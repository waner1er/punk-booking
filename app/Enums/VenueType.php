<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VenueType: string implements HasLabel
{
    case Club = 'club';
    case Bar = 'bar';
    case Squat = 'squat';
    case Festival = 'festival';
    case Association = 'association';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Club => 'Salle',
            self::Bar => 'Bar',
            self::Squat => 'Squat / lieu autogéré',
            self::Festival => 'Festival',
            self::Association => 'Asso',
            self::Other => 'Autre',
        };
    }
}
