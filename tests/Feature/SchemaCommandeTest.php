<?php

declare(strict_types=1);

use App\Enums\ModePaiement;
use App\Enums\StatutCommande;
use App\Enums\StatutLivreur;
use App\Enums\StatutPaiement;
use App\Enums\TypeCommande;
use App\Models\Commande;
use App\Models\Utilisateur;
use App\Models\Zone;

test('une commande se crée avec ses valeurs par défaut et ses relations', function () {
    $client = Utilisateur::factory()->client()->create();
    $livreur = Utilisateur::factory()->livreur()->create();
    $zone = Zone::create(['nom' => 'Centre-ville', 'slug' => 'centre-ville']);

    // Coordonnées d'exemple, approximatives
    $commande = Commande::create([
        'reference' => 'BLS-261101-0001',
        'client_id' => $client->id,
        'type' => TypeCommande::Colis,
        'retrait_zone_id' => $zone->id,
        'retrait_latitude' => 6.1067,
        'retrait_longitude' => -3.8603,
        'retrait_repere' => 'Devant le marché',
        'retrait_contact_telephone' => '+2250700000001',
        'livraison_latitude' => 6.1100,
        'livraison_longitude' => -3.8650,
        'livraison_repere' => 'Près de la pharmacie',
        'livraison_contact_telephone' => '+2250700000002',
        'description' => 'Un sac de documents',
        'frais_livraison' => 500,
        'montant_total' => 500,
        'mode_paiement' => ModePaiement::Especes,
        'code_livraison' => '4821',
    ])->fresh();

    expect($commande->statut)->toBe(StatutCommande::EnAttente);
    expect($commande->statut_paiement)->toBe(StatutPaiement::EnAttente);
    expect($commande->montant_articles)->toBe(0);
    expect($commande->client->is($client))->toBeTrue();
    expect($commande->toArray())->not->toHaveKey('code_livraison');

    $commande->update(['livreur_id' => $livreur->id]);

    expect($livreur->livraisons)->toHaveCount(1);
    expect($livreur->fresh()->profilLivreur->statut)->toBe(StatutLivreur::Disponible);
    expect($livreur->hasRole('livreur'))->toBeTrue();
});
