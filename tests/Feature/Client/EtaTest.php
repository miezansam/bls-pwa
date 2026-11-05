<?php

declare(strict_types=1);

use App\Enums\StatutCommande;
use App\Services\ItineraireService;
use Database\Seeders\TarifsOfficielsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(TarifsOfficielsSeeder::class);
    config([
        'bls.gps.store' => 'array',
        'bls.gps.precision_max_m' => 100,
        'bls.gps.verifier_region' => true,
        'bls.itineraire.url' => 'https://osrm.test',
    ]);
    Cache::flush();
    Http::preventStrayRequests();
    $this->travelTo('2026-11-04 12:00:00');
});

it('lit la durée et la distance renvoyées par OSRM', function () {
    Http::fake(['osrm.test/*' => Http::response(['code' => 'Ok', 'routes' => [['duration' => 312.4, 'distance' => 2410.8]]])]);

    expect(app(ItineraireService::class)->estimer(6.10, -3.86, 6.12, -3.85))
        ->toBe(['duree_s' => 312, 'distance_m' => 2411, 'source' => 'osrm']);
});

it('se rabat sur l\'estimation quand OSRM est en panne', function () {
    Http::fake(['osrm.test/*' => Http::response('', 500)]);

    $trajet = app(ItineraireService::class)->estimer(6.10, -3.86, 6.12, -3.85);

    expect($trajet['source'])->toBe('estimation')
        ->and($trajet['duree_s'])->toBeGreaterThan(0);
});

it('ne questionne OSRM qu\'une fois pour le même trajet', function () {
    Http::fake(['osrm.test/*' => Http::response(['code' => 'Ok', 'routes' => [['duration' => 300, 'distance' => 2000]]])]);

    app(ItineraireService::class)->estimer(6.10, -3.86, 6.12, -3.85);
    app(ItineraireService::class)->estimer(6.10, -3.86, 6.12, -3.85);

    Http::assertSentCount(1);
});

it('affiche l\'estimation d\'arrivée au client quand la livraison est épinglée', function () {
    Http::fake(['osrm.test/*' => Http::response(['code' => 'Ok', 'routes' => [['duration' => 600, 'distance' => 3000]]])]);

    $livreur = livreurValide();
    $commande = courseAcceptee($livreur);
    $commande->update([
        'statut' => StatutCommande::EnRouteLivraison,
        'livraison_epingle' => true,
        'livraison_latitude' => 6.12,
        'livraison_longitude' => -3.85,
    ]);
    envoyerPosition($this, $livreur, 6.1067, -3.8606);

    $this->actingAs($commande->client)
        ->get(route('client.commande', ['commande' => $commande->reference]))
        ->assertOk()
        ->assertSee('Livraison estimée : environ 10 min');
});

it('n\'affiche pas d\'estimation sans épingle et n\'appelle pas OSRM', function () {
    Http::fake();

    $livreur = livreurValide();
    $commande = courseAcceptee($livreur);
    $commande->update(['statut' => StatutCommande::EnRouteLivraison]);
    envoyerPosition($this, $livreur, 6.1067, -3.8606);

    $this->actingAs($commande->client)
        ->get(route('client.commande', ['commande' => $commande->reference]))
        ->assertOk()
        ->assertDontSee('Livraison estimée');

    Http::assertNothingSent();
});

it('masque une estimation d\'arrivée absurde', function () {
    Http::fake(['osrm.test/*' => Http::response(['code' => 'Ok', 'routes' => [['duration' => 20000, 'distance' => 90000]]])]);

    $livreur = livreurValide();
    $commande = courseAcceptee($livreur);
    $commande->update([
        'statut' => StatutCommande::EnRouteLivraison,
        'livraison_epingle' => true,
        'livraison_latitude' => 6.12,
        'livraison_longitude' => -3.85,
    ]);
    envoyerPosition($this, $livreur, 6.1067, -3.8606);

    $this->actingAs($commande->client)
        ->get(route('client.commande', ['commande' => $commande->reference]))
        ->assertOk()
        ->assertDontSee('Livraison estimée');
});
