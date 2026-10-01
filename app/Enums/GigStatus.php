<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GigStatus: string implements HasColor, HasLabel
{
    case Prospect = 'prospect';
    case Contacted = 'contacted';
    case Discussing = 'discussing';
    case Option = 'option';
    case Confirmed = 'confirmed';
    case Played = 'played';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Prospect => 'Piste',
            self::Contacted => 'Contacté',
            self::Discussing => 'En discussion',
            self::Option => 'Option posée',
            self::Confirmed => 'Confirmé',
            self::Played => 'Joué',
            self::Declined => 'Refusé',
            self::Cancelled => 'Annulé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Prospect => 'gray',
            self::Contacted, self::Discussing => 'info',
            self::Option => 'warning',
            self::Confirmed, self::Played => 'success',
            self::Declined, self::Cancelled => 'danger',
        };
    }

    /** Statuts « en cours » : ceux qu'on peut encore relancer. */
    public static function open(): array
    {
        return [
            self::Prospect->value,
            self::Contacted->value,
            self::Discussing->value,
            self::Option->value,
        ];
    }
}
