<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\EstimationPrix;
use App\Data\OptionsCommande;
use App\Enums\CodeOption;
use App\Enums\ModeTarif;
use App\Enums\RegleOption;
use App\Enums\TypeCommande;
use App\Exceptions\LivraisonNonDisponibleException;
use App\Exceptions\TarifIntrouvableException;
use App\Models\OptionTarifaire;
use App\Models\Tarif;
use App\Models\Zone;
use App\Support\Geo;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

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
        ?OptionsCommande $options = null,
    ): EstimationPrix {
        $options ??= new OptionsCommande();
        $quand = $options->quand ?? now();

        $distanceM = (int) round(
            Geo::distanceEnMetres($retraitLatitude, $retraitLongitude, $livraisonLatitude, $livraisonLongitude)
            * (float) config('bls.facteur_routier')
        );

        $dureeS = (int) round($distanceM / 1000 / (float) config('bls.vitesse_moyenne_kmh') * 3600);

        $this->verifierLivraisonDeNuit($zoneRetraitId, $zoneLivraisonId, $quand);

        $base = $this->prixDeBase($type, $zoneRetraitId, $zoneLivraisonId, $distanceM);
        $actives = $this->optionsActives($type, $options, $quand);

        // Les « prix minimum » relèvent un prix bas sans jamais le baisser, et ne se cumulent pas entre eux.
        // Les « suppléments » s'ajoutent.
        $planchers = $actives->filter(fn (OptionTarifaire $o): bool => $o->regle === RegleOption::Minimum);
        $supplements = $actives->filter(fn (OptionTarifaire $o): bool => $o->regle === RegleOption::Supplement);

        $total = $this->arrondirAuSuperieur(
            max($base['montant'], (int) $planchers->max('montant')) + (int) $supplements->sum('montant')
        );

        return new EstimationPrix(
            fraisLivraison: $total,
            distanceM: $distanceM,
            dureeEstimeeS: $dureeS,
            tarifId: $base['tarif_id'],
            detail: [
                'source' => $base['source'],
                'base' => $base['montant'],
                ...$base['infos'],
                'options' => $actives
                    ->map(fn (OptionTarifaire $o): array => [
                        'code' => $o->code->value,
                        'regle' => $o->regle->value,
                        'montant' => $o->montant,
                    ])
                    ->values()
                    ->all(),
                'total' => $total,
            ],
        );
    }

    /** Pas de livraison de nuit vers ou depuis une zone qui ne l'accepte pas (les villages). */
    private function verifierLivraisonDeNuit(?int $zoneRetraitId, ?int $zoneLivraisonId, CarbonInterface $quand): void
    {
        $ids = array_values(array_filter([$zoneRetraitId, $zoneLivraisonId]));

        if ($ids === [] || ! $this->estDeNuit($this->optionNuit(), $quand)) {
            return;
        }

        $zone = Zone::query()->whereIn('id', $ids)->where('accepte_livraison_nuit', false)->first();

        if ($zone !== null) {
            throw LivraisonNonDisponibleException::deNuit($zone->nom);
        }
    }

    /**
     * Ordre de priorité : tarif exceptionnel de la paire de zones, puis prix de la zone de destination,
     * puis tarif au kilomètre.
     *
     * @return array{montant: int, source: string, tarif_id: ?int, infos: array<string, mixed>}
     */
    private function prixDeBase(TypeCommande $type, ?int $zoneRetraitId, ?int $zoneLivraisonId, int $distanceM): array
    {
        $paire = $this->tarifForfait($type, $zoneRetraitId, $zoneLivraisonId);

        if ($paire !== null) {
            return [
                'montant' => max($paire->frais_forfait, $paire->frais_minimum),
                'source' => 'tarif_paire',
                'tarif_id' => $paire->id,
                'infos' => ['tarif' => $paire->nom],
            ];
        }

        $zones = $this->prixDeLaDestination($zoneRetraitId, $zoneLivraisonId);

        if ($zones !== null) {
            return $zones + ['source' => 'zones', 'tarif_id' => null];
        }

        $distance = $this->tarifDistance($type);

        if ($distance === null) {
            throw TarifIntrouvableException::pour($type);
        }

        return [
            'montant' => max(
                $distance->frais_base + (int) ceil(($distanceM * $distance->frais_par_km) / 1000),
                $distance->frais_minimum,
            ),
            'source' => 'distance',
            'tarif_id' => $distance->id,
            'infos' => ['tarif' => $distance->nom, 'distance_m' => $distanceM],
        ];
    }

    /**
     * Le prix est celui de la zone de destination. Les deux zones doivent être desservies
     * (actives et avec un prix), sinon la commande n'est pas livrable.
     *
     * @return array{montant: int, infos: array<string, mixed>}|null
     */
    private function prixDeLaDestination(?int $zoneRetraitId, ?int $zoneLivraisonId): ?array
    {
        if ($zoneRetraitId === null || $zoneLivraisonId === null) {
            return null;
        }

        $zones = Zone::query()->whereIn('id', [$zoneRetraitId, $zoneLivraisonId])->get()->keyBy('id');
        $retrait = $zones->get($zoneRetraitId);
        $livraison = $zones->get($zoneLivraisonId);

        foreach ([$retrait, $livraison] as $zone) {
            if ($zone === null || ! $zone->est_active || $zone->frais_livraison === null) {
                return null;
            }
        }

        return [
            'montant' => $livraison->frais_livraison,
            'infos' => [
                'zone_retrait' => ['nom' => $retrait->nom, 'frais' => $retrait->frais_livraison],
                'zone_livraison' => ['nom' => $livraison->nom, 'frais' => $livraison->frais_livraison],
            ],
        ];
    }

    /** @return Collection<int, OptionTarifaire> Les options actives qui s'appliquent à cette commande */
    private function optionsActives(TypeCommande $type, OptionsCommande $options, CarbonInterface $quand): Collection
    {
        return OptionTarifaire::query()
            ->where('est_active', true)
            ->get()
            ->filter(fn (OptionTarifaire $option): bool => match ($option->code) {
                CodeOption::Express => $options->express,
                CodeOption::DoubleCourse => $options->doubleCourse,
                CodeOption::CommandeSpeciale => $options->speciale,
                CodeOption::Nuit => $this->estDeNuit($option, $quand),
                CodeOption::RepasMultiples => $type === TypeCommande::Repas
                    && $options->nombreRepas >= ($option->nombre_minimum ?? 4),
            })
            ->values();
    }

    private function optionNuit(): ?OptionTarifaire
    {
        return OptionTarifaire::query()->where('code', CodeOption::Nuit->value)->first();
    }

    /** Gère aussi une nuit qui passe minuit (20 h à 6 h). Sans réglage en base : 20 h à 6 h. */
    private function estDeNuit(?OptionTarifaire $option, CarbonInterface $quand): bool
    {
        $heure = (int) $quand->format('G');
        $debut = $option?->heure_debut ?? 20;
        $fin = $option?->heure_fin ?? 6;

        return $debut > $fin
            ? ($heure >= $debut || $heure < $fin)
            : ($heure >= $debut && $heure < $fin);
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
