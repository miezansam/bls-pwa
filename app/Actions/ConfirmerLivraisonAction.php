<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StatutCommande;
use App\Exceptions\CodeLivraisonInvalideException;
use App\Models\Commande;
use App\Models\Utilisateur;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ConfirmerLivraisonAction
{
    public function __construct(private readonly ChangerStatutCommandeAction $changerStatut)
    {
    }

    public function executer(Commande $commande, string $code, Utilisateur $livreur): Commande
    {
        $bloque = false;
        $codeCorrect = false;

        // L'incrémentation des tentatives doit être validée AVANT de lancer l'exception,
        // sinon la transaction l'annulerait. D'où ces deux indicateurs.
        DB::transaction(function () use ($commande, $code, $livreur, &$bloque, &$codeCorrect) {
            $verrouillee = Commande::query()->lockForUpdate()->findOrFail($commande->id);

            if ($verrouillee->livreur_id !== $livreur->id) {
                throw new DomainException('Cette commande n\'est pas attribuée à ce livreur.');
            }

            if ($verrouillee->code_livraison_tentatives >= (int) config('bls.code_livraison_max_tentatives')) {
                $bloque = true;

                return;
            }

            if (hash_equals($verrouillee->code_livraison, $code)) {
                $codeCorrect = true;

                return;
            }

            $verrouillee->increment('code_livraison_tentatives');
        });

        if ($bloque) {
            throw CodeLivraisonInvalideException::bloque();
        }

        if (! $codeCorrect) {
            throw CodeLivraisonInvalideException::incorrect();
        }

        return $this->changerStatut->executer(
            $commande,
            StatutCommande::Livree,
            auteur: $livreur,
            codeLivraisonVerifie: true,
        );
    }
}
