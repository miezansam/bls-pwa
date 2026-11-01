<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthentificationException;
use App\Models\Utilisateur;

final class ComptesService
{
    /** Compte actif qui peut utiliser l'application (client ou livreur). */
    public function trouverPourConnexion(string $telephone, bool $verrou = false): Utilisateur
    {
        $requete = Utilisateur::query()->where('telephone', $telephone);

        if ($verrou) {
            $requete->lockForUpdate();
        }

        $utilisateur = $requete->first();

        if ($utilisateur === null) {
            throw AuthentificationException::compteIntrouvable();
        }

        if (! $utilisateur->est_actif) {
            throw AuthentificationException::compteDesactive();
        }

        if (! $utilisateur->aAccesALApplication()) {
            throw AuthentificationException::espaceNonAutorise();
        }

        return $utilisateur;
    }

    /** Un compte supprimé réserve toujours son numéro (l'index unique de la base le compte aussi). */
    public function verifierNumeroLibre(string $telephone): void
    {
        if (Utilisateur::withTrashed()->where('telephone', $telephone)->exists()) {
            throw AuthentificationException::compteExistant();
        }
    }
}
