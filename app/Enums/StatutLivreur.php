<?php

declare(strict_types=1);

namespace App\Enums;

enum StatutLivreur: string
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
}
