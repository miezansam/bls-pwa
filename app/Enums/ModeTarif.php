<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModeTarif: string implements HasLabel
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

    public function getLabel(): string
    {
        return $this->libelle();
    }
}
