<?php

declare(strict_types=1);

namespace App\Enums;

enum ModeTarif: string
{
    case ForfaitZone = 'forfait_zone';
    case Distance = 'distance';

    public function libelle(): string
    {
        return match ($this) {
            self::ForfaitZone => 'Forfait par zone',
            self::Distance => 'Au kilomètre',
        };
    }
}
