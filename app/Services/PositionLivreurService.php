<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatutCommande;
use App\Events\PositionLivreurMiseAJour;
use App\Models\Commande;
use App\Models\PositionLivreur;
use App\Models\Utilisateur;
use App\Support\Geo;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

final class PositionLivreurService
{
    /**
     * Retient la position si le livreur est en course, si elle est précise et plausible.
     * Le téléphone envoie l'âge de la mesure (et non une heure) : l'horloge du téléphone peut être fausse.
     */
    /**
     * Retient la position si le livreur est en course, si elle est précise et plausible.
     * Le téléphone envoie l'âge de la mesure (et non une heure) : l'horloge du téléphone peut être fausse.
     *
     * @return string|null null si la position est retenue, sinon la raison du rejet
     */
    public function enregistrer(Utilisateur $livreur, float $latitude, float $longitude, ?float $precisionM, int $ageMs): ?string
    {
        $reglages = config('bls.gps');
        $commande = $this->courseDeSuivi($livreur);

        if ($commande === null) {
            return 'course';
        }

        if ($ageMs > $reglages['age_max_secondes'] * 1000) {
            return 'age';
        }

        if ($precisionM !== null && $precisionM > $reglages['precision_max_m']) {
            return 'precision';
        }

        if ($reglages['verifier_region'] && ! $this->dansLaRegion($latitude, $longitude)) {
            return 'region';
        }

        $mesureS = now()->getTimestamp() - intdiv($ageMs, 1000);
        $cache = $this->cache();
        $cle = self::cle($livreur->id);
        $derniere = $cache->get($cle);

        if ($derniere !== null && ! $this->estPlausible($cache, $cle, $derniere, $latitude, $longitude, $mesureS)) {
            return 'filtre';
        }

        $cache->put($cle, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'mesure_s' => $mesureS,
        ], $reglages['ttl_position_secondes']);

        // Un point en base environ toutes les 30 secondes (add() n'écrit que si la clé n'existe pas)
        if ($cache->add($cle . ':historise', true, $reglages['intervalle_historique_secondes'])) {
            PositionLivreur::create([
                'livreur_id' => $livreur->id,
                'commande_id' => $commande->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'precision_m' => $precisionM !== null ? (int) round($precisionM) : null,
                'enregistree_le' => now()->subSeconds(intdiv($ageMs, 1000)),
            ]);
        }

        try {
            PositionLivreurMiseAJour::dispatch($commande->id, $latitude, $longitude, $mesureS);
        } catch (BroadcastException $e) {
            report($e);   // Reverb coupé : la position reste enregistrée, le suivi en direct reprendra
        }

        return null;
    }

    /** @return array{latitude: float, longitude: float}|null */
    public function derniere(int $livreurId): ?array
    {
        $position = $this->cache()->get(self::cle($livreurId));

        return $position === null ? null : ['latitude' => $position['latitude'], 'longitude' => $position['longitude']];
    }

    private function courseDeSuivi(Utilisateur $livreur): ?Commande
    {
        $statuts = collect(StatutCommande::cases())
            ->filter(fn (StatutCommande $statut): bool => $statut->declencheSuiviGps())
            ->map(fn (StatutCommande $statut): string => $statut->value)
            ->all();

        return Commande::query()->duLivreur($livreur->id)->whereIn('statut', $statuts)->latest('id')->first();
    }

    private function dansLaRegion(float $latitude, float $longitude): bool
    {
        $limites = config('bls.carte_limites');

        return $latitude >= $limites['latitude'][0] && $latitude <= $limites['latitude'][1]
            && $longitude >= $limites['longitude'][0] && $longitude <= $limites['longitude'][1];
    }

    /**
     * Écarte les points anciens, en double, ou qui impliquent une vitesse impossible.
     * Après 3 rejets de suite, on se recale : sinon un premier point faux bloquerait tous les suivants.
     *
     * @param array{latitude: float, longitude: float, mesure_s: int} $derniere
     */
    private function estPlausible(Repository $cache, string $cle, array $derniere, float $latitude, float $longitude, int $mesureS): bool
    {
        if ($mesureS <= $derniere['mesure_s']) {
            return false;
        }

        $secondes = $mesureS - $derniere['mesure_s'];

        if ($secondes > 60) {
            return true;
        }

        $metres = Geo::distanceEnMetres($derniere['latitude'], $derniere['longitude'], $latitude, $longitude);

        if ($metres / $secondes * 3.6 <= (float) config('bls.gps.vitesse_max_kmh')) {
            $cache->forget($cle . ':rejets');

            return true;
        }

        $rejets = (int) $cache->get($cle . ':rejets', 0);

        if ($rejets >= 3) {
            $cache->forget($cle . ':rejets');

            return true;
        }

        $cache->put($cle . ':rejets', $rejets + 1, 60);

        return false;
    }

    private function cache(): Repository
    {
        return Cache::store(config('bls.gps.store'));
    }

    private static function cle(int $livreurId): string
    {
        return 'position:livreur:' . $livreurId;
    }

    /** @return array{latitude: float, longitude: float, age_s: int}|null */
    public function derniereAvecAge(int $livreurId): ?array
    {
        $position = $this->cache()->get(self::cle($livreurId));

        if ($position === null) {
            return null;
        }

        return [
            'latitude' => $position['latitude'],
            'longitude' => $position['longitude'],
            'age_s' => max(0, now()->getTimestamp() - $position['mesure_s']),
        ];
    }
}
