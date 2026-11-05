<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Geo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class ItineraireService
{
    /** @return array{duree_s: int, distance_m: int, source: string} */
    public function estimer(float $deLatitude, float $deLongitude, float $versLatitude, float $versLongitude): array
    {
        $cle = sprintf('itineraire:%.4f,%.4f:%.4f,%.4f', $deLatitude, $deLongitude, $versLatitude, $versLongitude);

        return Cache::remember(
            $cle,
            (int) config('bls.itineraire.cache_secondes'),
            fn (): array => $this->osrm($deLatitude, $deLongitude, $versLatitude, $versLongitude)
                ?? $this->estimation($deLatitude, $deLongitude, $versLatitude, $versLongitude),
        );
    }

    /** @return array{duree_s: int, distance_m: int, source: string}|null */
    private function osrm(float $deLatitude, float $deLongitude, float $versLatitude, float $versLongitude): ?array
    {
        $base = config('bls.itineraire.url');

        if (blank($base)) {
            return null;
        }

        // OSRM attend la longitude avant la latitude
        $url = sprintf(
            '%s/route/v1/driving/%s,%s;%s,%s',
            rtrim((string) $base, '/'),
            $this->coordonnee($deLongitude),
            $this->coordonnee($deLatitude),
            $this->coordonnee($versLongitude),
            $this->coordonnee($versLatitude),
        );

        try {
            $reponse = Http::timeout((int) config('bls.itineraire.timeout_secondes'))
                ->connectTimeout(2)
                ->withUserAgent('BLS-PWA/1.0')
                ->acceptJson()
                ->get($url, ['overview' => 'false']);
        } catch (ConnectionException) {
            return null;
        }

        if (! $reponse->successful() || $reponse->json('code') !== 'Ok') {
            return null;
        }

        $route = $reponse->json('routes.0');

        if (! is_array($route) || ! isset($route['duration'], $route['distance'])) {
            return null;
        }

        return [
            'duree_s' => (int) round($route['duration']),
            'distance_m' => (int) round($route['distance']),
            'source' => 'osrm',
        ];
    }

    /** @return array{duree_s: int, distance_m: int, source: string} */
    private function estimation(float $deLatitude, float $deLongitude, float $versLatitude, float $versLongitude): array
    {
        $distance = (int) round(
            Geo::distanceEnMetres($deLatitude, $deLongitude, $versLatitude, $versLongitude)
            * (float) config('bls.facteur_routier')
        );

        return [
            'duree_s' => (int) round($distance / 1000 / (float) config('bls.vitesse_moyenne_kmh') * 3600),
            'distance_m' => $distance,
            'source' => 'estimation',
        ];
    }

    private function coordonnee(float $valeur): string
    {
        return number_format($valeur, 6, '.', '');
    }
}
