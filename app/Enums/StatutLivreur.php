<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatutLivreur: string implements HasColor, HasLabel
{
    case HorsLigne = 'hors_ligne';
    case Disponible = 'disponible';
    case Occupe = 'occupe';

    public function libelle(): string
    {
        return match ($this) {
            self::HorsLigne => 'Indisponible',
            self::Disponible => 'Disponible',
            self::Occupe => 'Occupé',
        };
    }
    public function getLabel(): string
    {
        return $this->libelle();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::HorsLigne => 'gray',
            self::Disponible => 'success',
            self::Occupe => 'warning',
        };
    }
}
