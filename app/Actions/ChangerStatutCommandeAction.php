<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModePaiement;
use App\Enums\StatutCommande;
use App\Enums\StatutLivreur;
use App\Enums\StatutPaiement;
use App\Events\StatutCommandeChange;
use App\Exceptions\CodeLivraisonInvalideException;
use App\Exceptions\TransitionInvalideException;
use App\Models\Commande;
use App\Models\HistoriqueStatut;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use App\Enums\StatutAttribution;

final class ChangerStatutCommandeAction
{
    public function executer(
        Commande $commande,
        StatutCommande $nouveauStatut,
        ?Utilisateur $auteur = null,
        ?Utilisateur $livreur = null,
        ?string $note = null,
        ?float $latitude = null,
        ?float $longitude = null,
        bool $codeLivraisonVerifie = false,
    ): Commande {
        return DB::transaction(function () use ($commande, $nouveauStatut, $auteur, $livreur, $note, $latitude, $longitude, $codeLivraisonVerifie) {
            // Verrou : deux changements simultanés sur la même commande sont traités l'un après l'autre
            $verrouillee = Commande::query()->lockForUpdate()->findOrFail($commande->id);
            $ancienStatut = $verrouillee->statut;

            if (! $ancienStatut->peutPasserA($nouveauStatut)) {
                throw TransitionInvalideException::entre($ancienStatut, $nouveauStatut);
            }

            if ($nouveauStatut === StatutCommande::Livree && ! $codeLivraisonVerifie) {
                throw CodeLivraisonInvalideException::nonVerifie();
            }

            if ($nouveauStatut === StatutCommande::Acceptee && $livreur === null) {
                throw new InvalidArgumentException('Un livreur est requis pour accepter une commande.');
            }

            $livreurIdActuel = $verrouillee->livreur_id;
            $maintenant = now();

            $champs = match ($nouveauStatut) {
                StatutCommande::Acceptee => ['livreur_id' => $livreur?->id, 'acceptee_le' => $maintenant],
                StatutCommande::EnAttente => ['livreur_id' => null, 'attribuee_le' => null, 'acceptee_le' => null],
                StatutCommande::ColisRecupere => ['recuperee_le' => $maintenant],
                StatutCommande::Livree => ['livree_le' => $maintenant],
                StatutCommande::Annulee => [
                    'annulee_le' => $maintenant,
                    'annulee_par' => $auteur?->id,
                    'motif_annulation' => $note,
                ],
                default => [],
            };

            $verrouillee->update(['statut' => $nouveauStatut] + $champs);

            HistoriqueStatut::create([
                'commande_id' => $verrouillee->id,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $nouveauStatut,
                'auteur_id' => $auteur?->id,
                'note' => $nouveauStatut === StatutCommande::Annulee ? null : $note,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            if ($nouveauStatut === StatutCommande::Livree) {
                $this->encaisserEspeces($verrouillee);
            }

            if ($nouveauStatut === StatutCommande::Annulee) {
                $this->annulerPaiementEnAttente($verrouillee);
                $this->cloturerOffresEnCours($verrouillee);
            }

            $this->mettreAJourStatutLivreur($nouveauStatut, $livreur?->id ?? $livreurIdActuel);

            StatutCommandeChange::dispatch($verrouillee, $ancienStatut, $nouveauStatut);

            return $verrouillee->refresh();
        });
    }

    private function encaisserEspeces(Commande $commande): void
    {
        if ($commande->mode_paiement !== ModePaiement::Especes) {
            return;
        }

        $commande->paiements()
            ->where('mode', ModePaiement::Especes->value)
            ->where('statut', StatutPaiement::EnAttente->value)
            ->update(['statut' => StatutPaiement::Paye->value, 'paye_le' => now()]);

        $commande->update(['statut_paiement' => StatutPaiement::Paye]);

        // Le livreur se rembourse le repas avancé : il ne doit reverser que les frais de livraison
        ProfilLivreur::query()
            ->where('utilisateur_id', $commande->livreur_id)
            ->increment('solde_caisse', $commande->frais_livraison);
    }

    private function annulerPaiementEnAttente(Commande $commande): void
    {
        if ($commande->statut_paiement !== StatutPaiement::EnAttente) {
            return;   // un paiement déjà encaissé relève d'un remboursement (V2)
        }

        $commande->paiements()
            ->where('statut', StatutPaiement::EnAttente->value)
            ->update(['statut' => StatutPaiement::Echoue->value, 'motif_echec' => 'Commande annulée']);

        $commande->update(['statut_paiement' => StatutPaiement::Echoue]);
    }

    private function mettreAJourStatutLivreur(StatutCommande $nouveauStatut, ?int $livreurId): void
    {
        if ($livreurId === null) {
            return;
        }

        $profil = ProfilLivreur::query()->where('utilisateur_id', $livreurId)->lockForUpdate()->first();

        if ($profil === null) {
            return;
        }

        if ($nouveauStatut === StatutCommande::Acceptee) {
            $profil->update(['statut' => StatutLivreur::Occupe]);

            return;
        }

        $libere = in_array($nouveauStatut, [StatutCommande::EnAttente, StatutCommande::Livree, StatutCommande::Annulee], true);

        if (
            $libere
            && $profil->statut === StatutLivreur::Occupe
            && ! Commande::query()->enCours()->duLivreur($livreurId)->exists()
        ) {
            $profil->update(['statut' => StatutLivreur::Disponible]);
        }
    }

    private function cloturerOffresEnCours(Commande $commande): void
    {
        $commande->attributions()
            ->where('statut', StatutAttribution::Proposee->value)
            ->update(['statut' => StatutAttribution::Annulee->value]);
    }
}
