<?php

declare(strict_types=1);

use App\Enums\ModeTarif;
use App\Enums\TypeCommande;
use App\Exceptions\TarifIntrouvableException;
use App\Models\Tarif;
use App\Services\CalculerPrixService;

function estimerColis(?int $zoneRetraitId, ?int $zoneLivraisonId, float $latitudeLivraison = 6.1100, float $longitudeLivraison = -3.8650, TypeCommande $type = TypeCommande::Colis)
{
    return app(CalculerPrixService::class)->estimer(
        $type,
        $zoneRetraitId,
        $zoneLivraisonId,
        6.1067,
        -3.8603,
        $latitudeLivraison,
        $longitudeLivraison,
    );
}

test('le forfait de la paire de zones est appliqué', function () {
    $a = creerZone('Centre');
    $b = creerZone('Nord');
    creerTarifForfait($a, $b, 800);

    expect(estimerColis($a->id, $b->id)->fraisLivraison)->toBe(800);
});

test('le prix est arrondi au multiple supérieur de 50 FCFA', function () {
    $a = creerZone('Centre');
    $b = creerZone('Nord');
    creerTarifForfait($a, $b, 520);

    expect(estimerColis($a->id, $b->id)->fraisLivraison)->toBe(550);
});

test('un tarif propre au type de commande passe avant le tarif générique', function () {
    $a = creerZone('Centre');
    $b = creerZone('Nord');
    creerTarifForfait($a, $b, 800);
    creerTarifForfait($a, $b, 1000, TypeCommande::Repas);

    expect(estimerColis($a->id, $b->id, type: TypeCommande::Repas)->fraisLivraison)->toBe(1000);
    expect(estimerColis($a->id, $b->id, type: TypeCommande::Colis)->fraisLivraison)->toBe(800);
});

test('sans forfait, le barème au kilomètre applique son minimum sur un court trajet', function () {
    Tarif::create([
        'nom' => 'Au km', 'mode' => ModeTarif::Distance,
        'frais_base' => 300, 'frais_par_km' => 150, 'frais_minimum' => 500,
    ]);

    // ~636 m à vol d'oiseau, ~827 m estimés par la route : 300 + 125 = 425, relevé au minimum de 500
    expect(estimerColis(null, null)->fraisLivraison)->toBe(500);
});

test('sans forfait, le barème au kilomètre suit la distance sur un long trajet', function () {
    Tarif::create([
        'nom' => 'Au km', 'mode' => ModeTarif::Distance,
        'frais_base' => 300, 'frais_par_km' => 150, 'frais_minimum' => 500,
    ]);

    // 0,0433° de latitude ≈ 4 815 m, ×1,3 ≈ 6 259 m : 300 + 939 = 1 239, arrondi à 1 250
    $estimation = estimerColis(null, null, 6.1500, -3.8603);

    expect($estimation->distanceM)->toBeBetween(6250, 6270);
    expect($estimation->fraisLivraison)->toBe(1250);
});

test('une exception est levée quand aucun tarif ne correspond', function () {
    $a = creerZone('Centre');
    $b = creerZone('Nord');

    expect(fn () => estimerColis($a->id, $b->id))->toThrow(TarifIntrouvableException::class);
});
