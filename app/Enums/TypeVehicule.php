<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypeVehicule: string implements HasLabel
{
    case Moto = 'moto';
    case Velo = 'velo';
    case Voiture = 'voiture';
    case Pieton = 'pieton';

    public function libelle(): string
    {
        return match ($this) {
            self::Moto => 'Moto',
            self::Velo => 'Vélo',
            self::Voiture => 'Voiture',
            self::Pieton => 'À pied',
        };
    }

    public function getLabel(): string
    {
        return $this->libelle();
    }
}
