<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TypeCommande: string implements HasLabel
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

    public function getLabel(): string
    {
        return $this->libelle();
    }
}
