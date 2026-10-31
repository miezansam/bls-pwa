<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ModeTarif;
use App\Models\Tarif;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DONNÉES D'EXEMPLE : à remplacer par les vrais quartiers d'Adzopé
 * et les vrais prix (modifiables ensuite dans l'admin).
 */
class ZonesEtTarifsSeeder extends Seeder
{
    private const FORFAIT_MEME_ZONE = 500;
    private const FORFAIT_ZONES_DIFFERENTES = 800;

    public function run(): void
    {
        $zones = collect(['Centre', 'Nord', 'Sud', 'Est', 'Ouest'])->map(
            fn (string $nom) => Zone::updateOrCreate(['slug' => Str::slug($nom)], ['nom' => $nom])
        );

        foreach ($zones as $depart) {
            foreach ($zones as $arrivee) {
                Tarif::updateOrCreate(
                    [
                        'mode' => ModeTarif::ForfaitZone->value,
                        'type_commande' => null,
                        'zone_retrait_id' => $depart->id,
                        'zone_livraison_id' => $arrivee->id,
                    ],
                    [
                        'nom' => "Forfait {$depart->nom} vers {$arrivee->nom}",
                        'frais_forfait' => $depart->is($arrivee)
                            ? self::FORFAIT_MEME_ZONE
                            : self::FORFAIT_ZONES_DIFFERENTES,
                        'est_actif' => true,
                    ],
                );
            }
        }

        // Barème au kilomètre, désactivé : il sert de repli si on l'active un jour
        Tarif::updateOrCreate(
            ['mode' => ModeTarif::Distance->value, 'type_commande' => null, 'zone_retrait_id' => null, 'zone_livraison_id' => null],
            ['nom' => 'Barème au kilomètre (exemple)', 'frais_base' => 300, 'frais_par_km' => 150, 'frais_minimum' => 500, 'est_actif' => false],
        );
    }
}
