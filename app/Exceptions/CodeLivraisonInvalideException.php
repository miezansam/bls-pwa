<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

final class CodeLivraisonInvalideException extends DomainException
{
    public static function incorrect(): self
    {
        return new self('Le code de livraison est incorrect.');
    }

    public static function bloque(): self
    {
        return new self('Trop de tentatives : contactez l\'administrateur pour finaliser cette livraison.');
    }

    public static function nonVerifie(): self
    {
        return new self('La livraison ne peut être confirmée sans vérification du code.');
    }
}
