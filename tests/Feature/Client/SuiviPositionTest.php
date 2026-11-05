<?php

declare(strict_types=1);

use App\Enums\StatutCommande;
use App\Events\PositionLivreurMiseAJour;
use App\Services\PositionLivreurService;
use Database\Seeders\TarifsOfficielsSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(TarifsOfficielsSeeder::class);
    config([
        'bls.gps.store' => 'array',
        'bls.gps.precision_max_m' => 100,
        'bls.gps.verifier_region' => true,
    ]);
    Cache::store('array')->flush();
    $this->travelTo('2026-11-04 12:00:00');
});

it('montre la carte et la dernière position au client pendant la course', function () {
    $livreur = livreurValide();
    $commande = courseAcceptee($livreur);
    envoyerPosition($this, $livreur, 6.1067, -3.8606);

    $this->actingAs($commande->client)
        ->get(route('client.commande', ['commande' => $commande->reference]))
        ->assertOk()
        ->assertSee('Votre livreur')
        ->assertSee('6.1067', false);
});

it('ne montre pas la carte avant l\'acceptation ni après la livraison', function () {
    $livreur = livreurValide();
    $enAttente = offrePour($livreur)->commande;

    $this->actingAs($enAttente->client)
        ->get(route('client.commande', ['commande' => $enAttente->reference]))
        ->assertDontSee('Votre livreur');

    $commande = courseAcceptee(livreurValide());
    $commande->update(['statut' => StatutCommande::Livree]);

    $this->actingAs($commande->client)
        ->get(route('client.commande', ['commande' => $commande->reference]))
        ->assertDontSee('Votre livreur');
});

it('donne l\'âge de la dernière position à partir de l\'horloge du serveur', function () {
    $livreur = livreurValide();
    courseAcceptee($livreur);
    envoyerPosition($this, $livreur, 6.1067, -3.8606);

    $this->travel(20)->seconds();

    expect(app(PositionLivreurService::class)->derniereAvecAge($livreur->id))
        ->toMatchArray(['latitude' => 6.1067, 'longitude' => -3.8606, 'age_s' => 20]);
});

it('diffuse la position avec son âge et sans autre donnée', function () {
    $evenement = new PositionLivreurMiseAJour(7, 6.1067, -3.8606, now()->getTimestamp() - 7);

    expect($evenement->broadcastWith())->toBe(['latitude' => 6.1067, 'longitude' => -3.8606, 'age_s' => 7])
        ->and($evenement->broadcastOn()[0]->name)->toBe('private-commande.7');
});
