<?php

declare(strict_types=1);

namespace App\Enums;

enum ObjectifOtp: string
{
    case Connexion = 'connexion';
    case Inscription = 'inscription';
}
