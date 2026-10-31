<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StatutAttribution;
use App\Models\Attribution;
use App\Models\Commande;
use App\Models\Utilisateur;
use DomainException;
use Illuminate\Support\Facades\DB;

final class RefuserAttributionAction
{
    public function executer(Attribution $attribution, Utilisateur $livreur): Attribution
    {
        return DB::transaction(function () use ($attribution, $livreur) {
            $verrouillee = Attribution::query()->lockForUpdate()->findOrFail($attribution->id);

            if ($verrouillee->livreur_id !== $livreur->id) {
                throw new DomainException('Cette offre ne vous est pas destinée.');
            }

            if ($verrouillee->statut !== StatutAttribution::Proposee) {
                throw new DomainException('Cette offre n\'est plus valable.');
            }

            $verrouillee->update(['statut' => StatutAttribution::Refusee, 'repondue_le' => now()]);

            // La commande n'est plus attribuée : l'admin pourra la proposer à quelqu'un d'autre
            Commande::query()->whereKey($verrouillee->commande_id)->update(['attribuee_le' => null]);

            return $verrouillee->refresh();
        });
    }
}
