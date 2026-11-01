<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CodeOption: string implements HasLabel
{
    case Express = 'express';
    case Nuit = 'nuit';
    case DoubleCourse = 'double_course';
    case CommandeSpeciale = 'commande_speciale';
    case RepasMultiples = 'repas_multiples';

    public function libelle(): string
    {
        return match ($this) {
            self::Express => 'Livraison express / urgente',
            self::Nuit => 'Livraison de nuit',
            self::DoubleCourse => 'Double course / double commande',
            self::CommandeSpeciale => 'Commande spéciale (gâteaux, commandes sensibles)',
            self::RepasMultiples => 'Plusieurs commandes de repas',
        };
    }

    public function getLabel(): string
    {
        return $this->libelle();
    }
}
