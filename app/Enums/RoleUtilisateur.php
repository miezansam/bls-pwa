<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleUtilisateur: string
{
    case Client = 'client';
    case Livreur = 'livreur';
    case Admin = 'admin';

    public function libelle(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Livreur => 'Livreur',
            self::Admin => 'Administrateur',
        };
    }
}
