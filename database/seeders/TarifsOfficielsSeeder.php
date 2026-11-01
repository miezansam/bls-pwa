<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CodeOption;
use App\Enums\RegleOption;
use App\Models\OptionTarifaire;
use App\Models\Zone;
use Illuminate\Database\Seeder;

/**
 * Tarifs officiels de l'Association des livreurs d'Adzopé (affiche de novembre 2026).
 *
 * « firstOrCreate » : ce seeder ne réécrit jamais ce que l'administrateur a modifié.
 */
class TarifsOfficielsSeeder extends Seeder
{
    private const VILLE = 'Livraison normale (tous les quartiers qui ne sont pas listés ci-dessous)';
    private const SPECIALE = 'Zone spécifique';
    private const VILLAGE = 'Village ou zone éloignée (pas de livraison de nuit)';

    public function run(): void
    {
        // nom, slug, prix de livraison vers la zone, livraison de nuit possible, description
        $zones = [
            ['Ville d\'Adzopé', 'ville-adzope', 500, true, self::VILLE],

            ['Corridor d\'Akoupé', 'corridor-akoupe', 700, true, self::SPECIALE],
            ['Corridor d\'Adjikoi', 'corridor-adjikoi', 700, true, self::SPECIALE],
            ['Nouveau Château', 'nouveau-chateau', 700, true, self::SPECIALE],
            ['Mapketabois Extension', 'mapketabois-extension', 700, true, self::SPECIALE],
            ['TP Konate Extension', 'tp-konate-extension', 700, true, self::SPECIALE],
            ['Aqua Parc', 'aqua-parc', 700, true, self::SPECIALE],
            ['Bernard Dadie', 'bernard-dadie', 700, true, self::SPECIALE],

            ['Miadzin', 'miadzin', 1000, false, self::VILLAGE],
            ['Biasso', 'biasso', 1000, false, self::VILLAGE],
            ['Moapé', 'moape', 1000, false, self::VILLAGE],

            ['Ananguié', 'ananguie', 1500, false, self::VILLAGE],
            ['Diapé', 'diape', 1500, false, self::VILLAGE],
            ['Sap l\'âme', 'sap-l-ame', 1500, false, self::VILLAGE],
            ['Zodji', 'zodji', 1500, false, self::VILLAGE],
            ['Institut Raoul Follereau', 'institut-raoul-follereau', 1500, false, self::VILLAGE],

            ['Agou', 'agou', 2000, false, self::VILLAGE],
            ['Yakassé-Attobrou', 'yakasse-attobrou', 2000, false, self::VILLAGE],
        ];

        foreach ($zones as [$nom, $slug, $frais, $nuit, $description]) {
            Zone::firstOrCreate(
                ['slug' => $slug],
                [
                    'nom' => $nom,
                    'description' => $description,
                    'frais_livraison' => $frais,
                    'accepte_livraison_nuit' => $nuit,
                    'est_active' => true,
                ],
            );
        }

        // Les options à 1000 FCFA sont des « prix minimum ». La nuit va de 20 h à 6 h (l'affiche ne donne
        // pas l'heure de fin : à confirmer).
        $options = [
            [CodeOption::Express, null, null, null],
            [CodeOption::Nuit, 20, 6, null],
            [CodeOption::DoubleCourse, null, null, null],
            [CodeOption::CommandeSpeciale, null, null, null],
            [CodeOption::RepasMultiples, null, null, 4],
        ];

        foreach ($options as [$code, $heureDebut, $heureFin, $nombreMinimum]) {
            OptionTarifaire::firstOrCreate(
                ['code' => $code->value],
                [
                    'regle' => RegleOption::Minimum,
                    'montant' => 1000,
                    'heure_debut' => $heureDebut,
                    'heure_fin' => $heureFin,
                    'nombre_minimum' => $nombreMinimum,
                    'est_active' => true,
                ],
            );
        }
    }
}
