<?php

declare(strict_types=1);

use App\Livewire\Client\NouvelleCommande;
use App\Models\Commande;
use App\Models\Utilisateur;
use App\Models\Zone;
use Database\Seeders\TarifsOfficielsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function clientConnecte(): Utilisateur
{
    Role::findOrCreate('client');
    $client = Utilisateur::factory()->create();
    $client->assignRole('client');

    return $client;
}

function formulaire(Zone $retrait, Zone $livraison): array
{
    return [
        'retraitZoneId' => (string) $retrait->id,
        'retraitRepere' => 'Devant la pharmacie',
        'retraitTelephone' => '0700000000',
        'livraisonZoneId' => (string) $livraison->id,
        'livraisonRepere' => 'Portail bleu',
        'livraisonTelephone' => '0700000001',
        'description' => 'Un sac de riz',
    ];
}

beforeEach(function () {
    $this->seed(TarifsOfficielsSeeder::class);
    $this->ville = Zone::query()->where('frais_livraison', 500)->firstOrFail();
    $this->village = Zone::query()->where('accepte_livraison_nuit', false)->where('est_active', true)->firstOrFail();
});

it('affiche le prix de la zone de destination en direct', function () {
    $this->travelTo('2026-11-04 12:00:00');

    Livewire::actingAs(clientConnecte())
        ->test(NouvelleCommande::class)
        ->set('retraitZoneId', (string) $this->ville->id)
        ->set('livraisonZoneId', (string) $this->ville->id)
        ->assertSee('500 FCFA');
});

it('crée la commande en espèces et redirige vers le suivi', function () {
    $this->travelTo('2026-11-04 12:00:00');
    $client = clientConnecte();

    Livewire::actingAs($client)
        ->test(NouvelleCommande::class)
        ->set(formulaire($this->ville, $this->ville))
        ->call('enregistrer')
        ->assertHasNoErrors();

    $commande = Commande::query()->firstOrFail();

    expect($commande->client_id)->toBe($client->id)
        ->and($commande->frais_livraison)->toBe(500)
        ->and($commande->mode_paiement->estMobileMoney())->toBeFalse()
        ->and($commande->code_livraison)->toHaveLength(4);
});

it('refuse la livraison de nuit vers un village', function () {
    $this->travelTo('2026-11-04 22:00:00');

    Livewire::actingAs(clientConnecte())
        ->test(NouvelleCommande::class)
        ->set(formulaire($this->ville, $this->village))
        ->call('enregistrer')
        ->assertHasErrors('livraisonZoneId');

    expect(Commande::query()->count())->toBe(0);
});

it('montre le code de livraison au client et le cache aux autres', function () {
    $this->travelTo('2026-11-04 12:00:00');
    $client = clientConnecte();

    Livewire::actingAs($client)
        ->test(NouvelleCommande::class)
        ->set(formulaire($this->ville, $this->ville))
        ->call('enregistrer');

    $commande = Commande::query()->firstOrFail();
    $url = route('client.commande', ['commande' => $commande->reference]);

    $this->actingAs($client)->get($url)->assertOk()->assertSee($commande->code_livraison);
    $this->actingAs(clientConnecte())->get($url)->assertNotFound();
});

it('propose de commander et de suivre la commande en cours depuis l\'accueil', function () {
    $this->travelTo('2026-11-04 12:00:00');
    $client = clientConnecte();

    $this->actingAs($client)->get(route('client.accueil'))
        ->assertOk()
        ->assertSee(route('client.commander'), false)
        ->assertDontSee('Commande en cours');

    Livewire::actingAs($client)
        ->test(NouvelleCommande::class)
        ->set(formulaire($this->ville, $this->ville))
        ->call('enregistrer');

    $commande = Commande::query()->firstOrFail();

    $this->actingAs($client)->get(route('client.accueil'))
        ->assertSee('Commande en cours')
        ->assertSee($commande->reference);
});
