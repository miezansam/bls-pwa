<?php

declare(strict_types=1);

namespace App\Enums;

enum StatutAttribution: string
{
    case Proposee = 'proposee';
    case Acceptee = 'acceptee';
    case Refusee = 'refusee';
    case Expiree = 'expiree';
    case Annulee = 'annulee';

    public function libelle(): string
    {
        return match ($this) {
            self::Proposee => 'Proposée',
            self::Acceptee => 'Acceptée',
            self::Refusee => 'Refusée',
            self::Expiree => 'Expirée',
            self::Annulee => 'Annulée',
        };
    }
}
