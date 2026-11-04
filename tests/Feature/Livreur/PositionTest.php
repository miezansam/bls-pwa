<?php

declare(strict_types=1);

use App\Events\PositionLivreurMiseAJour;
use App\Models\PositionLivreur;
use App\Models\Utilisateur;
use App\Services\PositionLivreurService;
use Database\Seeders\TarifsOfficielsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

function envoyerPosition($test, Utilisateur $livreur, float $latitude, float $longitude, array $extra = [])
{
    return $test->actingAs($livreur)->postJson(route('livreur.position'), array_merge([
        'latitude' => $latitude,
        'longitude' => $longitude,
        'precision_m' => 12,
        'age_ms' => 0,
    ], $extra));
}

function dernierePosition(Utilisateur $livreur): ?array
{
    return app(PositionLivreurService::class)->derniere($livreur->id);
}

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

it('retient une position valide pendant une course, l\'historise et la diffuse', function () {
    Event::fake([PositionLivreurMiseAJour::class]);
    $livreur = livreurValide();
    $commande = courseAcceptee($livreur);

    envoyerPosition($this, $livreur, 6.1067, -3.8606)->assertNoContent();

    expect(dernierePosition($livreur))->toBe(['latitude' => 6.1067, 'longitude' => -3.8606])
        ->and(PositionLivreur::query()->where('commande_id', $commande->id)->count())->toBe(1);

    Event::assertDispatched(PositionLivreurMiseAJour::class, fn (PositionLivreurMiseAJour $e): bool => $e->commandeId === $commande->id);
});

it('n\'enregistre en base qu\'un point toutes les 30 secondes', function () {
    $livreur = livreurValide();
    courseAcceptee($livreur);

    envoyerPosition($this, $livreur, 6.1067, -3.8606);
    $this->travel(4)->seconds();
    envoyerPosition($this, $livreur, 6.1069, -3.8606);

    expect(PositionLivreur::query()->count())->toBe(1);

    $this->travel(30)->seconds();
    envoyerPosition($this, $livreur, 6.1072, -3.8606);

    expect(PositionLivreur::query()->count())->toBe(2);
});

it('ignore les positions d\'un livreur sans course en cours', function () {
    $livreur = livreurValide();

    envoyerPosition($this, $livreur, 6.1067, -3.8606)->assertNoContent();

    expect(dernierePosition($livreur))->toBeNull()
        ->and(PositionLivreur::query()->count())->toBe(0);
});

it('écarte une position imprécise ou hors de la région', function () {
    $livreur = livreurValide();
    courseAcceptee($livreur);

    envoyerPosition($this, $livreur, 6.1067, -3.8606, ['precision_m' => 500]);
    envoyerPosition($this, $livreur, 48.85, 2.35);

    expect(dernierePosition($livreur))->toBeNull();
});

it('écarte un saut impossible, puis se recale après trois rejets', function () {
    $livreur = livreurValide();
    courseAcceptee($livreur);
    envoyerPosition($this, $livreur, 6.1067, -3.8606);

    for ($i = 0; $i < 3; $i++) {
        $this->travel(2)->seconds();
        envoyerPosition($this, $livreur, 6.16, -3.8606);

        expect(dernierePosition($livreur)['latitude'])->toBe(6.1067);
    }

    $this->travel(2)->seconds();
    envoyerPosition($this, $livreur, 6.16, -3.8606);

    expect(dernierePosition($livreur)['latitude'])->toBe(6.16);
});

it('ignore une position plus ancienne que la dernière retenue', function () {
    $livreur = livreurValide();
    courseAcceptee($livreur);

    envoyerPosition($this, $livreur, 6.1067, -3.8606);
    envoyerPosition($this, $livreur, 6.1068, -3.8606, ['age_ms' => 5000]);

    expect(dernierePosition($livreur)['latitude'])->toBe(6.1067);
});

it('interdit l\'envoi de position à un client', function () {
    $client = offrePour(livreurValide())->commande->client;

    envoyerPosition($this, $client, 6.1067, -3.8606)->assertForbidden();
});


it('indique au téléphone pourquoi une position est écartée', function () {
    $livreur = livreurValide();
    courseAcceptee($livreur);

    envoyerPosition($this, $livreur, 6.1067, -3.8606, ['precision_m' => 500])
        ->assertNoContent()
        ->assertHeader('X-Position-Ecartee', 'precision');

    envoyerPosition($this, $livreur, 48.85, 2.35)
        ->assertNoContent()
        ->assertHeader('X-Position-Ecartee', 'region');

    envoyerPosition($this, livreurValide(), 6.1067, -3.8606)
        ->assertNoContent()
        ->assertHeader('X-Position-Ecartee', 'course');
});
