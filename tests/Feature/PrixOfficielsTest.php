<?php

declare(strict_types=1);

use App\Data\OptionsCommande;
use App\Enums\CodeOption;
use App\Enums\RegleOption;
use App\Enums\TypeCommande;
use App\Exceptions\TarifIntrouvableException;
use App\Models\OptionTarifaire;
use App\Models\Zone;
use App\Services\CalculerPrixService;
use Carbon\CarbonImmutable;
use Database\Seeders\TarifsOfficielsSeeder;
use App\Exceptions\LivraisonNonDisponibleException;

function prixEntre(?Zone $retrait, ?Zone $livraison, ?OptionsCommande $options = null, TypeCommande $type = TypeCommande::Colis)
{
    return app(CalculerPrixService::class)->estimer(
        $type,
        $retrait?->id,
        $livraison?->id,
        6.1067,
        -3.8603,
        6.1100,
        -3.8650,
        $options,
    );
}

test('le prix suit la zone', function () {
    $ville = creerZoneAvecFrais('Ville', 500);
    $aquaParc = creerZoneAvecFrais('Aqua Parc', 700);
    $agou = creerZoneAvecFrais('Agou', 2000);

    expect(prixEntre($ville, $ville)->fraisLivraison)->toBe(500);
    expect(prixEntre($ville, $aquaParc)->fraisLivraison)->toBe(700);
    expect(prixEntre($ville, $agou)->fraisLivraison)->toBe(2000);
});

test('le prix est celui de la zone de destination', function () {
    $ville = creerZoneAvecFrais('Ville', 500);
    $aquaParc = creerZoneAvecFrais('Aqua Parc', 700);
    $agou = creerZoneAvecFrais('Agou', 2000);

    expect(prixEntre($agou, $ville)->fraisLivraison)->toBe(500);
    expect(prixEntre($aquaParc, $agou)->fraisLivraison)->toBe(2000);
    expect(prixEntre($aquaParc, $ville)->fraisLivraison)->toBe(500);
});

test('une zone sans prix ou désactivée n\'est pas desservie', function () {
    $ville = creerZoneAvecFrais('Ville', 500);
    $sansPrix = creerZoneAvecFrais('Sans prix', null);
    $desactivee = creerZoneAvecFrais('Désactivée', 700, active: false);

    expect(fn () => prixEntre($ville, $sansPrix))->toThrow(TarifIntrouvableException::class);
    expect(fn () => prixEntre($ville, $desactivee))->toThrow(TarifIntrouvableException::class);
});

test('un tarif exceptionnel pour une paire remplace le prix des zones', function () {
    $ville = creerZoneAvecFrais('Ville', 500);
    $agou = creerZoneAvecFrais('Agou', 2000);
    creerTarifForfait($ville, $agou, 1800);

    expect(prixEntre($ville, $agou)->fraisLivraison)->toBe(1800);
});

test('une option « prix minimum » relève un prix bas mais jamais un prix élevé', function () {
    creerOption(CodeOption::Express, 1000);
    $ville = creerZoneAvecFrais('Ville', 500);
    $agou = creerZoneAvecFrais('Agou', 2000);
    $express = new OptionsCommande(express: true);

    expect(prixEntre($ville, $ville, $express)->fraisLivraison)->toBe(1000);
    expect(prixEntre($ville, $agou, $express)->fraisLivraison)->toBe(2000);
});

test('la livraison de nuit s\'applique de 20 h à 6 h', function () {
    creerOption(CodeOption::Nuit, 1000, surcharges: ['heure_debut' => 20, 'heure_fin' => 6]);
    $ville = creerZoneAvecFrais('Ville', 500);

    $prixA = fn (int $heure): int => prixEntre(
        $ville,
        $ville,
        new OptionsCommande(quand: CarbonImmutable::create(2026, 11, 1, $heure)),
    )->fraisLivraison;

    expect($prixA(19))->toBe(500);
    expect($prixA(20))->toBe(1000);
    expect($prixA(23))->toBe(1000);
    expect($prixA(2))->toBe(1000);
    expect($prixA(5))->toBe(1000);
    expect($prixA(6))->toBe(500);
});

test('la double course et la commande spéciale ont leur prix minimum', function () {
    creerOption(CodeOption::DoubleCourse, 1000);
    creerOption(CodeOption::CommandeSpeciale, 1000);
    $ville = creerZoneAvecFrais('Ville', 500);

    expect(prixEntre($ville, $ville)->fraisLivraison)->toBe(500);
    expect(prixEntre($ville, $ville, new OptionsCommande(doubleCourse: true))->fraisLivraison)->toBe(1000);
    expect(prixEntre($ville, $ville, new OptionsCommande(speciale: true))->fraisLivraison)->toBe(1000);
});

test('plusieurs repas : le prix change à partir de 4, et seulement pour un repas', function () {
    creerOption(CodeOption::RepasMultiples, 1000, surcharges: ['nombre_minimum' => 4]);
    $ville = creerZoneAvecFrais('Ville', 500);

    expect(prixEntre($ville, $ville, new OptionsCommande(nombreRepas: 3), TypeCommande::Repas)->fraisLivraison)->toBe(500);
    expect(prixEntre($ville, $ville, new OptionsCommande(nombreRepas: 4), TypeCommande::Repas)->fraisLivraison)->toBe(1000);
    expect(prixEntre($ville, $ville, new OptionsCommande(nombreRepas: 4), TypeCommande::Colis)->fraisLivraison)->toBe(500);
});

test('plusieurs prix minimum ne se cumulent pas', function () {
    creerOption(CodeOption::Express, 1000);
    creerOption(CodeOption::Nuit, 1000, surcharges: ['heure_debut' => 20, 'heure_fin' => 6]);
    $ville = creerZoneAvecFrais('Ville', 500);

    $prix = prixEntre($ville, $ville, new OptionsCommande(express: true, quand: CarbonImmutable::create(2026, 11, 1, 21)));

    expect($prix->fraisLivraison)->toBe(1000);
});

test('une option « supplément » s\'ajoute au prix de base', function () {
    creerOption(CodeOption::Express, 300, RegleOption::Supplement);
    $ville = creerZoneAvecFrais('Ville', 500);
    $agou = creerZoneAvecFrais('Agou', 2000);
    $express = new OptionsCommande(express: true);

    expect(prixEntre($ville, $ville, $express)->fraisLivraison)->toBe(800);
    expect(prixEntre($ville, $agou, $express)->fraisLivraison)->toBe(2300);
});

test('une option désactivée est ignorée', function () {
    creerOption(CodeOption::Express, 1000, surcharges: ['est_active' => false]);
    $ville = creerZoneAvecFrais('Ville', 500);

    expect(prixEntre($ville, $ville, new OptionsCommande(express: true))->fraisLivraison)->toBe(500);
});

test('le détail du calcul est conservé', function () {
    creerOption(CodeOption::Express, 1000);
    $ville = creerZoneAvecFrais('Ville', 500);
    $aquaParc = creerZoneAvecFrais('Aqua Parc', 700);

    $detail = prixEntre($ville, $aquaParc, new OptionsCommande(express: true))->detail;

    expect($detail['source'])->toBe('zones');
    expect($detail['base'])->toBe(700);
    expect($detail['total'])->toBe(1000);
    expect($detail['options'])->toHaveCount(1);
    expect($detail['options'][0]['code'])->toBe('express');
    expect($detail['zone_livraison']['nom'])->toBe('Aqua Parc');
});

test('les tarifs officiels de l\'affiche sont chargés en base', function () {
    $this->seed(TarifsOfficielsSeeder::class);

    expect(Zone::count())->toBe(18);

    $prix = Zone::query()->pluck('frais_livraison', 'slug');
    expect($prix['ville-adzope'])->toBe(500);
    expect($prix['corridor-akoupe'])->toBe(700);
    expect($prix['bernard-dadie'])->toBe(700);
    expect($prix['miadzin'])->toBe(1000);
    expect($prix['moape'])->toBe(1000);
    expect($prix['ananguie'])->toBe(1500);
    expect($prix['institut-raoul-follereau'])->toBe(1500);
    expect($prix['agou'])->toBe(2000);
    expect($prix['yakasse-attobrou'])->toBe(2000);

    expect(Zone::where('accepte_livraison_nuit', false)->count())->toBe(10);
    expect(Zone::where('slug', 'ville-adzope')->firstOrFail()->accepte_livraison_nuit)->toBeTrue();
    expect(Zone::where('slug', 'agou')->firstOrFail()->accepte_livraison_nuit)->toBeFalse();
    expect(OptionTarifaire::count())->toBe(5);
    expect(OptionTarifaire::where('code', CodeOption::Nuit->value)->firstOrFail()->heure_debut)->toBe(20);
    expect(OptionTarifaire::where('code', CodeOption::RepasMultiples->value)->firstOrFail()->nombre_minimum)->toBe(4);
});

test('relancer le seeder ne réécrit pas les modifications de l\'admin', function () {
    $this->seed(TarifsOfficielsSeeder::class);
    Zone::where('slug', 'agou')->update(['frais_livraison' => 2500]);

    $this->seed(TarifsOfficielsSeeder::class);

    expect(Zone::count())->toBe(18);
    expect(Zone::where('slug', 'agou')->firstOrFail()->frais_livraison)->toBe(2500);
});

test('la livraison de nuit est refusée vers ou depuis une zone hors d\'Adzopé', function () {
    $ville = creerZoneAvecFrais('Ville', 500);
    $village = creerZoneAvecFrais('Agou', 2000, nuit: false);
    $nuit = new OptionsCommande(quand: CarbonImmutable::create(2026, 11, 1, 22));

    // Sans option « nuit » enregistrée, la fenêtre par défaut de 20 h à 6 h s'applique
    expect(fn () => prixEntre($ville, $village, $nuit))->toThrow(LivraisonNonDisponibleException::class);
    expect(fn () => prixEntre($village, $ville, $nuit))->toThrow(LivraisonNonDisponibleException::class);
});

test('de jour, un village reste desservi', function () {
    $ville = creerZoneAvecFrais('Ville', 500);
    $village = creerZoneAvecFrais('Agou', 2000, nuit: false);

    foreach ([6, 14, 19] as $heure) {
        $options = new OptionsCommande(quand: CarbonImmutable::create(2026, 11, 1, $heure));

        expect(prixEntre($ville, $village, $options)->fraisLivraison)->toBe(2000);
    }
});
