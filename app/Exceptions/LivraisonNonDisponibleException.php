<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

final class LivraisonNonDisponibleException extends DomainException
{
    public static function deNuit(string $zone): self
    {
        return new self("La livraison de nuit n'est pas disponible vers ou depuis « {$zone} » : elle est limitée à Adzopé.");
    }
}
