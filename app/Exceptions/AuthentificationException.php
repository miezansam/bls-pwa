<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

final class AuthentificationException extends DomainException
{
    private function __construct(string $message, public readonly string $motif)
    {
        parent::__construct($message);
    }

    public static function telephoneInvalide(): self
    {
        return new self('Numéro de téléphone invalide. Exemple : 07 00 00 00 00.', 'telephone_invalide');
    }

    public static function nomInvalide(): self
    {
        return new self('Veuillez indiquer votre nom (2 caractères minimum).', 'nom_invalide');
    }

    public static function compteIntrouvable(): self
    {
        return new self('Aucun compte ne correspond à ce numéro. Créez votre compte pour continuer.', 'compte_introuvable');
    }

    public static function compteExistant(): self
    {
        return new self('Un compte existe déjà avec ce numéro. Connectez-vous.', 'compte_existant');
    }

    public static function compteDesactive(): self
    {
        return new self('Ce compte est désactivé. Contactez l\'association.', 'compte_desactive');
    }

    public static function espaceNonAutorise(): self
    {
        return new self('Ce compte n\'a pas accès à cet espace.', 'espace_non_autorise');
    }

    public static function codeExpire(): self
    {
        return new self('Ce code a expiré ou n\'existe pas. Demandez-en un nouveau.', 'code_expire');
    }

    public static function codeIncorrect(int $essaisRestants): self
    {
        $message = $essaisRestants > 0
            ? "Code incorrect. Il vous reste {$essaisRestants} essai(s)."
            : 'Code incorrect. Demandez un nouveau code.';

        return new self($message, 'code_incorrect');
    }

    public static function codeBloque(): self
    {
        return new self('Trop d\'essais incorrects. Demandez un nouveau code.', 'code_bloque');
    }

    public static function tropDeDemandes(int $secondes): self
    {
        return new self("Patientez {$secondes} secondes avant de demander un nouveau code.", 'trop_de_demandes');
    }

    public static function limiteHoraire(): self
    {
        return new self('Trop de codes demandés. Réessayez dans une heure.', 'limite_horaire');
    }

    public static function smsIndisponible(): self
    {
        return new self('Impossible d\'envoyer le SMS pour le moment. Réessayez dans quelques instants.', 'sms_indisponible');
    }
}
