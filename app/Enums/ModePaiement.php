<?php

declare(strict_types=1);

namespace App\Enums;

enum ModePaiement: string
{
    case Especes = 'especes';
    case Wave = 'wave';
    case OrangeMoney = 'orange_money';
    case MtnMomo = 'mtn_momo';
    case MoovMoney = 'moov_money';

    public function libelle(): string
    {
        return match ($this) {
            self::Especes => 'Espèces à la livraison',
            self::Wave => 'Wave',
            self::OrangeMoney => 'Orange Money',
            self::MtnMomo => 'MTN MoMo',
            self::MoovMoney => 'Moov Money',
        };
    }

    public function estMobileMoney(): bool
    {
        return $this !== self::Especes;
    }
}
