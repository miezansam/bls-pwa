<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StatutAttribution;
use App\Enums\StatutCommande;
use App\Models\Attribution;
use App\Models\Commande;
use App\Models\Utilisateur;
use DomainException;
use Illuminate\Support\Facades\DB;

final class AccepterAttributionAction
{
    public function __construct(private readonly ChangerStatutCommandeAction $changerStatut)
    {
    }

    public function executer(Attribution $attribution, Utilisateur $livreur): Commande
    {
        $expiree = false;

        $commande = DB::transaction(function () use ($attribution, $livreur, &$expiree) {
            $verrouillee = Attribution::query()->lockForUpdate()->findOrFail($attribution->id);

            if ($verrouillee->livreur_id !== $livreur->id) {
                throw new DomainException('Cette offre ne vous est pas destinée.');
            }

            if ($verrouillee->statut !== StatutAttribution::Proposee) {
                throw new DomainException('Cette offre n\'est plus valable.');
            }

            // On constate l'expiration et on l'enregistre ; l'exception est levée APRÈS la transaction,
            // sinon le retour en arrière annulerait aussi le passage au statut « expirée ».
            if ($verrouillee->expire_le !== null && $verrouillee->expire_le->isPast()) {
                $verrouillee->update(['statut' => StatutAttribution::Expiree, 'repondue_le' => now()]);
                $expiree = true;

                return null;
            }

            $commande = $this->changerStatut->executer(
                $verrouillee->commande,
                StatutCommande::Acceptee,
                auteur: $livreur,
                livreur: $livreur,
            );

            $verrouillee->update(['statut' => StatutAttribution::Acceptee, 'repondue_le' => now()]);

            return $commande;
        });

        if ($expiree) {
            throw new DomainException('Cette offre a expiré.');
        }

        return $commande;
    }
}
