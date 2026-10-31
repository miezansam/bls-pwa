<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\StatutCommande;
use DomainException;

final class TransitionInvalideException extends DomainException
{
    public static function entre(StatutCommande $de, StatutCommande $vers): self
    {
        return new self("Impossible de passer de « {$de->libelle()} » à « {$vers->libelle()} ».");
    }
}
