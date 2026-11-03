<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StatutLivreur;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;
use DomainException;

final class ChangerDisponibiliteLivreurAction
{
    public function executer(Utilisateur $livreur, bool $disponible): ProfilLivreur
    {
        $profil = ProfilLivreur::query()->where('utilisateur_id', $livreur->id)->firstOrFail();

        if ($disponible && ! $profil->est_valide) {
            throw new DomainException('Votre compte livreur n\'est pas encore validé.');
        }

        // On ne bascule qu'entre « disponible » et « hors ligne » : pas pendant une course.
        if (! in_array($profil->statut, [StatutLivreur::Disponible, StatutLivreur::HorsLigne], true)) {
            throw new DomainException('Terminez votre course en cours avant de changer de mode.');
        }

        $profil->update(['statut' => $disponible ? StatutLivreur::Disponible : StatutLivreur::HorsLigne]);
        return $profil->refresh();
    }
}
