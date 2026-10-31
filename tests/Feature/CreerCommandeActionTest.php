<?php

declare(strict_types=1);

use App\Actions\CreerCommandeAction;
use App\Enums\ModePaiement;
use App\Enums\StatutCommande;
use App\Enums\StatutPaiement;
use App\Enums\TypeCommande;
use App\Models\Utilisateur;

function nouvelleCommande(array $surcharges = [], int $frais = 800)
{
    $depart = creerZone('Zone départ');
    $arrivee = creerZone('Zone arrivée');
    creerTarifForfait($depart, $arrivee, $frais);
    $client = Utilisateur::factory()->client()->create();

    return app(CreerCommandeAction::class)->executer($client, donneesCommande($depart, $arrivee, $surcharges));
}

test('une commande est créée en attente avec référence, code, historique et paiement', function () {
    $commande = nouvelleCommande();

    expect($commande->statut)->toBe(StatutCommande::EnAttente);
    expect($commande->reference)->toMatch('/^BLS-\d{6}-[A-Z2-9]{5}$/');
    expect($commande->code_livraison)->toMatch('/^\d{4}$/');
    expect($commande->frais_livraison)->toBe(800);
    expect($commande->montant_total)->toBe(800);
    expect($commande->statut_paiement)->toBe(StatutPaiement::EnAttente);

    expect($commande->historiqueStatuts)->toHaveCount(1);
    expect($commande->historiqueStatuts->first()->ancien_statut)->toBeNull();

    $paiement = $commande->dernierPaiement;
    expect($paiement->mode)->toBe(ModePaiement::Especes);
    expect($paiement->statut)->toBe(StatutPaiement::EnAttente);
    expect($paiement->montant)->toBe(800);
});

test('pour un repas, le montant des articles s\'ajoute au total', function () {
    $commande = nouvelleCommande(['type' => TypeCommande::Repas, 'montant_articles' => 2500]);

    expect($commande->montant_articles)->toBe(2500);
    expect($commande->montant_total)->toBe(3300);
});

test('pour un colis, le montant des articles est ignoré', function () {
    $commande = nouvelleCommande(['type' => TypeCommande::Colis, 'montant_articles' => 2500]);

    expect($commande->montant_articles)->toBe(0);
    expect($commande->montant_total)->toBe(800);
});

test('le paiement Mobile Money est refusé tant qu\'il n\'est pas activé', function () {
    expect(fn () => nouvelleCommande(['mode_paiement' => ModePaiement::Wave]))
        ->toThrow(DomainException::class);
});

test('un client désactivé ne peut pas commander', function () {
    $depart = creerZone('Zone départ');
    $arrivee = creerZone('Zone arrivée');
    creerTarifForfait($depart, $arrivee, 800);
    $client = Utilisateur::factory()->client()->create(['est_actif' => false]);

    expect(fn () => app(CreerCommandeAction::class)->executer($client, donneesCommande($depart, $arrivee)))
        ->toThrow(DomainException::class);
});
