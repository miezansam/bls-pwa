<?php

declare(strict_types=1);

namespace App\Enums;

enum StatutPaiement: string
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
}
