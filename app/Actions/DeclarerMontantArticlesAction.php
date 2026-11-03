<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StatutCommande;
use App\Enums\StatutPaiement;
use App\Enums\TypeCommande;
use App\Models\Commande;
use App\Models\Utilisateur;
use DomainException;
use Illuminate\Support\Facades\DB;

final class DeclarerMontantArticlesAction
{
    private const MONTANT_MAX = 200000;

    /** Le livreur déclare ce qu'il a payé pour les articles d'un repas. Met à jour le total et le paiement en attente. */
    public function executer(Commande $commande, int $montant, Utilisateur $livreur): Commande
    {
        if ($montant < 0 || $montant > self::MONTANT_MAX) {
            throw new DomainException('Le montant des articles n\'est pas valide.');
        }

        return DB::transaction(function () use ($commande, $montant, $livreur) {
            $verrouillee = Commande::query()->lockForUpdate()->findOrFail($commande->id);

            if ($verrouillee->livreur_id !== $livreur->id) {
                throw new DomainException('Cette commande n\'est pas attribuée à ce livreur.');
            }

            if ($verrouillee->type !== TypeCommande::Repas) {
                throw new DomainException('Le montant des articles ne concerne que les repas.');
            }

            $statutsAutorises = [StatutCommande::EnRouteRetrait, StatutCommande::ColisRecupere, StatutCommande::EnRouteLivraison];

            if (! in_array($verrouillee->statut, $statutsAutorises, true)) {
                throw new DomainException('Le montant des articles ne peut plus être modifié.');
            }

            $total = $verrouillee->frais_livraison + $montant;

            $verrouillee->update(['montant_articles' => $montant, 'montant_total' => $total]);

            $verrouillee->paiements()
                ->where('statut', StatutPaiement::EnAttente->value)
                ->update(['montant' => $total]);

            return $verrouillee->refresh();
        });
    }
}
