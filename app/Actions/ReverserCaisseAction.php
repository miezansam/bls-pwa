<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ProfilLivreur;
use App\Models\ReversementCaisse;
use App\Models\Utilisateur;
use App\Support\Format;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ReverserCaisseAction
{
    /** Enregistre des espèces remises par un livreur à l'admin et diminue le solde de sa caisse. */
    public function executer(ProfilLivreur $profil, int $montant, Utilisateur $admin, ?string $note = null): ReversementCaisse
    {
        if ($montant <= 0) {
            throw new DomainException('Le montant doit être supérieur à zéro.');
        }

        return DB::transaction(function () use ($profil, $montant, $admin, $note) {
            $verrouille = ProfilLivreur::query()->lockForUpdate()->findOrFail($profil->id);

            if ($montant > $verrouille->solde_caisse) {
                throw new DomainException(
                    'Le montant dépasse la caisse du livreur (' . Format::fcfa($verrouille->solde_caisse) . ').'
                );
            }

            $verrouille->decrement('solde_caisse', $montant);

            return ReversementCaisse::create([
                'livreur_id' => $verrouille->utilisateur_id,
                'recu_par' => $admin->id,
                'montant' => $montant,
                'note' => $note,
                'reverse_le' => now(),
            ]);
        });
    }
}
