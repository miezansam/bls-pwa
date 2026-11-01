<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatutPaiement: string implements HasColor, HasLabel
{
    case EnAttente = 'en_attente';
    case Paye = 'paye';
    case Echoue = 'echoue';
    case Rembourse = 'rembourse';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Paye => 'Payé',
            self::Echoue => 'Échoué',
            self::Rembourse => 'Remboursé',
        };
    }

    public function getLabel(): string
    {
        return $this->libelle();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EnAttente => 'gray',
            self::Paye => 'success',
            self::Echoue => 'danger',
            self::Rembourse => 'warning',
        };
    }
}
