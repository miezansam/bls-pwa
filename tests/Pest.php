<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()
    ->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function creerZone(string $nom): \App\Models\Zone
{
    return \App\Models\Zone::create(['nom' => $nom, 'slug' => \Illuminate\Support\Str::slug($nom)]);
}

function creerTarifForfait(
    \App\Models\Zone $depart,
    \App\Models\Zone $arrivee,
    int $frais,
    ?\App\Enums\TypeCommande $type = null,
): \App\Models\Tarif {
    return \App\Models\Tarif::create([
        'nom' => "Forfait {$depart->nom} vers {$arrivee->nom}",
        'mode' => \App\Enums\ModeTarif::ForfaitZone,
        'type_commande' => $type,
        'zone_retrait_id' => $depart->id,
        'zone_livraison_id' => $arrivee->id,
        'frais_forfait' => $frais,
    ]);
}

/** Données valides pour CreerCommandeAction (coordonnées d'exemple, approximatives). */
function donneesCommande(?\App\Models\Zone $zoneRetrait, ?\App\Models\Zone $zoneLivraison, array $surcharges = []): array
{
    return array_merge([
        'type' => \App\Enums\TypeCommande::Colis,
        'retrait' => [
            'zone_id' => $zoneRetrait?->id,
            'adresse_id' => null,
            'latitude' => 6.1067,
            'longitude' => -3.8603,
            'repere' => 'Devant le marché',
            'contact_nom' => null,
            'contact_telephone' => '+2250700000001',
        ],
        'livraison' => [
            'zone_id' => $zoneLivraison?->id,
            'adresse_id' => null,
            'latitude' => 6.1100,
            'longitude' => -3.8650,
            'repere' => 'Près de la pharmacie',
            'contact_nom' => null,
            'contact_telephone' => '+2250700000002',
        ],
        'description' => 'Un sac de documents',
        'photo_chemin' => null,
        'mode_paiement' => \App\Enums\ModePaiement::Especes,
        'montant_articles' => 0,
    ], $surcharges);
}

/** Une commande en attente, avec son client, ses zones et un forfait. */
function commandeDeTest(int $frais = 800): \App\Models\Commande
{
    $depart = creerZone('Zone départ');
    $arrivee = creerZone('Zone arrivée');
    creerTarifForfait($depart, $arrivee, $frais);

    $client = \App\Models\Utilisateur::factory()->client()->create();

    return app(\App\Actions\CreerCommandeAction::class)->executer($client, donneesCommande($depart, $arrivee));
}

/** @return array{0: \App\Models\Commande, 1: \App\Models\Utilisateur} commande en route vers la livraison et son livreur */
function courseEnRouteLivraison(int $frais = 800): array
{
    $commande = commandeDeTest($frais);
    $livreur = \App\Models\Utilisateur::factory()->livreur()->create();
    $changer = app(\App\Actions\ChangerStatutCommandeAction::class);

    $commande = $changer->executer($commande, \App\Enums\StatutCommande::Acceptee, auteur: $livreur, livreur: $livreur);
    $commande = $changer->executer($commande, \App\Enums\StatutCommande::EnRouteRetrait, auteur: $livreur);
    $commande = $changer->executer($commande, \App\Enums\StatutCommande::ColisRecupere, auteur: $livreur);
    $commande = $changer->executer($commande, \App\Enums\StatutCommande::EnRouteLivraison, auteur: $livreur);

    return [$commande, $livreur];
}

function creerZoneAvecFrais(string $nom, ?int $frais, bool $active = true, bool $nuit = true): \App\Models\Zone
{
    return \App\Models\Zone::create([
        'nom' => $nom,
        'slug' => \Illuminate\Support\Str::slug($nom),
        'frais_livraison' => $frais,
        'accepte_livraison_nuit' => $nuit,
        'est_active' => $active,
    ]);
}

function creerOption(
    \App\Enums\CodeOption $code,
    int $montant = 1000,
    \App\Enums\RegleOption $regle = \App\Enums\RegleOption::Minimum,
    array $surcharges = [],
): \App\Models\OptionTarifaire {
    return \App\Models\OptionTarifaire::create(array_merge([
        'code' => $code,
        'regle' => $regle,
        'montant' => $montant,
    ], $surcharges));
}
