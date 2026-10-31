<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\EstimationPrix;
use App\Enums\ModeTarif;
use App\Enums\TypeCommande;
use App\Exceptions\TarifIntrouvableException;
use App\Models\Tarif;
use App\Support\Geo;

final class CalculerPrixService
{
    public function estimer(
        TypeCommande $type,
        ?int $zoneRetraitId,
        ?int $zoneLivraisonId,
        float $retraitLatitude,
        float $retraitLongitude,
        float $livraisonLatitude,
        float $livraisonLongitude,
    ): EstimationPrix {
        $distanceM = (int) round(
            Geo::distanceEnMetres($retraitLatitude, $retraitLongitude, $livraisonLatitude, $livraisonLongitude)
            * (float) config('bls.facteur_routier')
        );

        $dureeS = (int) round($distanceM / 1000 / (float) config('bls.vitesse_moyenne_kmh') * 3600);

        $tarif = $this->tarifForfait($type, $zoneRetraitId, $zoneLivraisonId)
            ?? $this->tarifDistance($type);

        if ($tarif === null) {
            throw TarifIntrouvableException::pour($type);
        }

        $brut = $tarif->mode === ModeTarif::ForfaitZone
            ? $tarif->frais_forfait
            : $tarif->frais_base + (int) ceil(($distanceM * $tarif->frais_par_km) / 1000);

        return new EstimationPrix(
            fraisLivraison: $this->arrondirAuSuperieur(max($brut, $tarif->frais_minimum)),
            distanceM: $distanceM,
            dureeEstimeeS: $dureeS,
            tarifId: $tarif->id,
        );
    }

    private function tarifForfait(TypeCommande $type, ?int $zoneRetraitId, ?int $zoneLivraisonId): ?Tarif
    {
        if ($zoneRetraitId === null || $zoneLivraisonId === null) {
            return null;
        }

        return Tarif::query()
            ->where('est_actif', true)
            ->where('mode', ModeTarif::ForfaitZone->value)
            ->where('zone_retrait_id', $zoneRetraitId)
            ->where('zone_livraison_id', $zoneLivraisonId)
            ->where(fn ($q) => $q->whereNull('type_commande')->orWhere('type_commande', $type->value))
            ->orderByRaw('type_commande IS NULL')   // un tarif propre au type passe avant le tarif générique
            ->first();
    }

    private function tarifDistance(TypeCommande $type): ?Tarif
    {
        return Tarif::query()
            ->where('est_actif', true)
            ->where('mode', ModeTarif::Distance->value)
            ->where(fn ($q) => $q->whereNull('type_commande')->orWhere('type_commande', $type->value))
            ->orderByRaw('type_commande IS NULL')
            ->first();
    }

    private function arrondirAuSuperieur(int $montant): int
    {
        $pas = max(1, (int) config('bls.arrondi_fcfa'));

        return (int) (ceil($montant / $pas) * $pas);
    }
}
