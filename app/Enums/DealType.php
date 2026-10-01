<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DealType: string implements HasLabel
{
    case Fixed = 'fixed';
    case DoorDeal = 'door_deal';
    case Hat = 'hat';
    case Expenses = 'expenses';
    case Free = 'free';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => 'Cachet fixe',
            self::DoorDeal => '% des entrées',
            self::Hat => 'Au chapeau',
            self::Expenses => 'Défraiement',
            self::Free => 'Gratuit / soutien',
        };
    }
}
