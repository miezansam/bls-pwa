<?php

declare(strict_types=1);

namespace App\Support;

final class Geo
{
    private const RAYON_TERRE_M = 6_371_000;

    /** Distance à vol d'oiseau (formule de Haversine), en mètres. */
    public static function distanceEnMetres(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB,
    ): int {
        $phiA = deg2rad($latitudeA);
        $phiB = deg2rad($latitudeB);
        $deltaPhi = deg2rad($latitudeB - $latitudeA);
        $deltaLambda = deg2rad($longitudeB - $longitudeA);

        $a = sin($deltaPhi / 2) ** 2
            + cos($phiA) * cos($phiB) * sin($deltaLambda / 2) ** 2;

        return (int) round(2 * self::RAYON_TERRE_M * asin(min(1.0, sqrt($a))));
    }
}
