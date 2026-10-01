<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InteractionType: string implements HasLabel
{
    case Email = 'email';
    case Call = 'call';
    case Message = 'message';
    case Meeting = 'meeting';

    public function getLabel(): string
    {
        return match ($this) {
            self::Email => 'Mail',
            self::Call => 'Appel',
            self::Message => 'Message',
            self::Meeting => 'Rencontre',
        };
    }
}
