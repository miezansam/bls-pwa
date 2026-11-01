<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StatutAttribution;
use App\Enums\StatutLivreur;
use App\Models\Attribution;
use App\Models\Commande;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ChangerEtatCompteAction
{
    public function executer(Utilisateur $utilisateur, bool $actif, Utilisateur $admin): Utilisateur
    {
        return DB::transaction(function () use ($utilisateur, $actif, $admin) {
            if (! $actif) {
                if ($utilisateur->is($admin)) {
                    throw new DomainException('Vous ne pouvez pas désactiver votre propre compte.');
                }

                if (Commande::query()->enCours()->duLivreur($utilisateur->id)->exists()) {
                    throw new DomainException('Ce livreur a une course en cours : terminez-la ou annulez-la d\'abord.');
                }

                $this->annulerOffresEnCours($utilisateur);

                ProfilLivreur::query()
                    ->where('utilisateur_id', $utilisateur->id)
                    ->update(['statut' => StatutLivreur::HorsLigne->value]);
            }

            $utilisateur->update(['est_actif' => $actif]);

            return $utilisateur->refresh();
        });
    }

    private function annulerOffresEnCours(Utilisateur $livreur): void
    {
        $offres = Attribution::query()
            ->where('livreur_id', $livreur->id)
            ->where('statut', StatutAttribution::Proposee->value);

        $commandeIds = (clone $offres)->pluck('commande_id');

        $offres->update(['statut' => StatutAttribution::Annulee->value]);

        Commande::query()->whereIn('id', $commandeIds)->update(['attribuee_le' => null]);
    }
}
