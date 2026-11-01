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
    /** @var list<array{nom: string, slug: string, frais: int, description: string}> */
    private const ZONES = [
        ['nom' => 'Ville d\'Adzopé', 'slug' => 'ville-adzope', 'frais' => 500, 'description' => 'Livraison normale (tous les quartiers qui ne sont pas listés ci-dessous)'],

        ['nom' => 'Corridor d\'Akoupé', 'slug' => 'corridor-akoupe', 'frais' => 700, 'description' => 'Zone spécifique'],
        ['nom' => 'Corridor d\'Adjikoi', 'slug' => 'corridor-adjikoi', 'frais' => 700, 'description' => 'Zone spécifique'],
        ['nom' => 'Nouveau Château', 'slug' => 'nouveau-chateau', 'frais' => 700, 'description' => 'Zone spécifique'],
        ['nom' => 'Mapketabois Extension', 'slug' => 'mapketabois-extension', 'frais' => 700, 'description' => 'Zone spécifique'],
        ['nom' => 'TP Konate Extension', 'slug' => 'tp-konate-extension', 'frais' => 700, 'description' => 'Zone spécifique'],
        ['nom' => 'Aqua Parc', 'slug' => 'aqua-parc', 'frais' => 700, 'description' => 'Zone spécifique'],
        ['nom' => 'Bernard Dadie', 'slug' => 'bernard-dadie', 'frais' => 700, 'description' => 'Zone spécifique'],

        ['nom' => 'Miadzin', 'slug' => 'miadzin', 'frais' => 1000, 'description' => 'Village ou zone éloignée'],
        ['nom' => 'Biasso', 'slug' => 'biasso', 'frais' => 1000, 'description' => 'Village ou zone éloignée'],
        ['nom' => 'Moapé', 'slug' => 'moape', 'frais' => 1000, 'description' => 'Village ou zone éloignée'],

        ['nom' => 'Ananguié', 'slug' => 'ananguie', 'frais' => 1500, 'description' => 'Village ou zone éloignée'],
        ['nom' => 'Diapé', 'slug' => 'diape', 'frais' => 1500, 'description' => 'Village ou zone éloignée'],
        ['nom' => 'Sap l\'âme', 'slug' => 'sap-l-ame', 'frais' => 1500, 'description' => 'Village ou zone éloignée'],
        ['nom' => 'Zodji', 'slug' => 'zodji', 'frais' => 1500, 'description' => 'Village ou zone éloignée'],
        ['nom' => 'Institut Raoul Follereau', 'slug' => 'institut-raoul-follereau', 'frais' => 1500, 'description' => 'Village ou zone éloignée'],

        ['nom' => 'Agou', 'slug' => 'agou', 'frais' => 2000, 'description' => 'Village ou zone éloignée'],
        ['nom' => 'Yakassé-Attobrou', 'slug' => 'yakasse-attobrou', 'frais' => 2000, 'description' => 'Village ou zone éloignée'],
    ];

    public function run(): void
    {
        foreach (self::ZONES as $zone) {
            Zone::firstOrCreate(
                ['slug' => $zone['slug']],
                [
                    'nom' => $zone['nom'],
                    'description' => $zone['description'],
                    'frais_livraison' => $zone['frais'],
                    'est_active' => true,
                ],
            );
        }

        // Les options à 1000 FCFA sont lues comme des « prix minimum » (à confirmer).
        // La nuit va de 20 h à 6 h : l'affiche ne donne pas l'heure de fin.
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
