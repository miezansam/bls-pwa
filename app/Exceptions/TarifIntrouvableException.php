<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\TypeCommande;
use DomainException;

final class TarifIntrouvableException extends DomainException
{
    public static function pour(TypeCommande $type): self
    {
        return new self("Aucun tarif actif ne correspond à cette commande ({$type->libelle()}).");
    }
}
