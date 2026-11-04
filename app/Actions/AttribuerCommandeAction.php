<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAttribution;
use App\Enums\StatutCommande;
use App\Enums\StatutLivreur;
use App\Models\Attribution;
use App\Models\Commande;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;
use DomainException;
use Illuminate\Support\Facades\DB;
use App\Events\OffreProposee;

final class AttribuerCommandeAction
{
    /** Propose une commande en attente à un livreur. $admin = null pour une attribution automatique (V3). */
    public function executer(Commande $commande, Utilisateur $livreur, ?Utilisateur $admin = null): Attribution
    {
        return DB::transaction(function () use ($commande, $livreur, $admin) {
            $verrouillee = Commande::query()->lockForUpdate()->findOrFail($commande->id);

            if ($verrouillee->statut !== StatutCommande::EnAttente) {
                throw new DomainException('Seule une commande en attente peut être attribuée.');
            }

            $profil = ProfilLivreur::query()->where('utilisateur_id', $livreur->id)->first();

            if (! $livreur->est_actif || ! $livreur->hasRole(RoleUtilisateur::Livreur->value) || $profil === null || ! $profil->est_valide) {
                throw new DomainException('Ce livreur n\'est pas validé.');
            }

            if ($profil->statut === StatutLivreur::HorsLigne) {
                throw new DomainException('Ce livreur est indisponible.');
            }

            // Une seule offre active par commande
            Attribution::query()
                ->where('commande_id', $verrouillee->id)
                ->where('statut', StatutAttribution::Proposee->value)
                ->update(['statut' => StatutAttribution::Annulee->value]);

            $attribution = Attribution::create([
                'commande_id' => $verrouillee->id,
                'livreur_id' => $livreur->id,
                'attribuee_par' => $admin?->id,
                'statut' => StatutAttribution::Proposee,
                'proposee_le' => now(),
                'expire_le' => now()->addMinutes((int) config('bls.delai_reponse_attribution_minutes')),
            ]);

            $verrouillee->update(['attribuee_le' => now()]);

            OffreProposee::dispatch($attribution);

            return $attribution;
        });
    }
}
