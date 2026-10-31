<?php

declare(strict_types=1);

namespace App\Enums;

enum TypeCommande: string
{
    case Colis = 'colis';
    case Repas = 'repas';

    public function libelle(): string
    {
        return match ($this) {
            self::Colis => 'Colis',
            self::Repas => 'Repas',
        };
    }
}
