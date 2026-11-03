<?php

declare(strict_types=1);

use App\Actions\CreerCommandeAction;
use App\Enums\ModePaiement;
use App\Enums\StatutAttribution;
use App\Enums\StatutCommande;
use App\Enums\StatutLivreur;
use App\Enums\TypeCommande;
use App\Enums\TypeVehicule;
use App\Livewire\Livreur\Accueil;
use App\Models\Attribution;
use App\Models\Commande;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;
use App\Models\Zone;
use Database\Seeders\TarifsOfficielsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function livreurValide(bool $valide = true, StatutLivreur $statut = StatutLivreur::Disponible): Utilisateur
{
    Role::findOrCreate('livreur');
    $livreur = Utilisateur::factory()->create();
    $livreur->assignRole('livreur');

    ProfilLivreur::create([
        'utilisateur_id' => $livreur->id,
        'statut' => $statut,
        'type_vehicule' => TypeVehicule::cases()[0],
        'plaque' => 'AB 123 CD',
        'est_valide' => $valide,
    ]);

    return $livreur;
}

function offrePour(Utilisateur $livreur): Attribution
{
    Role::findOrCreate('client');
    $client = Utilisateur::factory()->create();
    $client->assignRole('client');
    $zone = Zone::query()->where('frais_livraison', 500)->firstOrFail();

    $lieu = fn (string $tel) => [
        'zone_id' => $zone->id, 'adresse_id' => null,
        'latitude' => 6.1067, 'longitude' => -3.8606,
        'repere' => 'Près du marché', 'contact_nom' => null, 'contact_telephone' => $tel,
    ];

    $commande = app(CreerCommandeAction::class)->executer($client, [
        'type' => TypeCommande::Colis,
        'retrait' => $lieu('0700000000'),
        'livraison' => $lieu('0700000001'),
        'description' => 'Un colis',
        'photo_chemin' => null,
        'mode_paiement' => collect(ModePaiement::cases())->first(fn ($m) => ! $m->estMobileMoney()),
        'montant_articles' => 0,
    ]);

    return Attribution::create([
        'commande_id' => $commande->id,
        'livreur_id' => $livreur->id,
        'statut' => StatutAttribution::Proposee,
        'proposee_le' => now(),
        'expire_le' => now()->addMinutes(5),
    ]);
}

beforeEach(function () {
    $this->seed(TarifsOfficielsSeeder::class);
    $this->travelTo('2026-11-04 12:00:00');
});

it('affiche l\'offre proposée au livreur et pas celle d\'un autre', function () {
    $moi = livreurValide();
    $autre = livreurValide();
    $mienne = offrePour($moi);
    $leurs = offrePour($autre);

    Livewire::actingAs($moi)
        ->test(Accueil::class)
        ->assertSee($mienne->commande->reference)
        ->assertDontSee($leurs->commande->reference);
});

it('accepte une offre : la commande est acceptée et attribuée au livreur', function () {
    $livreur = livreurValide();
    $offre = offrePour($livreur);

    Livewire::actingAs($livreur)->test(Accueil::class)->call('accepter', $offre->id);

    $commande = Commande::query()->findOrFail($offre->commande_id);

    expect($commande->statut)->toBe(StatutCommande::Acceptee)
        ->and($commande->livreur_id)->toBe($livreur->id)
        ->and($offre->refresh()->statut)->toBe(StatutAttribution::Acceptee);
});

it('refuse une offre : elle disparaît et la commande reste en attente', function () {
    $livreur = livreurValide();
    $offre = offrePour($livreur);

    Livewire::actingAs($livreur)
        ->test(Accueil::class)
        ->call('refuser', $offre->id)
        ->assertDontSee($offre->commande->reference);

    expect($offre->refresh()->statut)->toBe(StatutAttribution::Refusee)
        ->and($offre->commande->refresh()->statut)->toBe(StatutCommande::EnAttente);
});

it('ne permet pas d\'accepter l\'offre d\'un autre livreur', function () {
    $offre = offrePour(livreurValide());

    Livewire::actingAs(livreurValide())
        ->test(Accueil::class)
        ->call('accepter', $offre->id)
        ->assertStatus(404);

    expect($offre->refresh()->statut)->toBe(StatutAttribution::Proposee);
});

it('affiche une erreur claire pour une offre expirée', function () {
    $livreur = livreurValide();
    $offre = offrePour($livreur);
    $this->travel(10)->minutes();

    Livewire::actingAs($livreur)
        ->test(Accueil::class)
        ->call('accepter', $offre->id)
        ->assertSet('erreur', 'Cette offre a expiré.');
});

it('bascule entre disponible et hors ligne', function () {
    $livreur = livreurValide();
    $statut = fn () => ProfilLivreur::query()->where('utilisateur_id', $livreur->id)->firstOrFail()->statut;

    Livewire::actingAs($livreur)->test(Accueil::class)->call('basculer');
    expect($statut())->toBe(StatutLivreur::HorsLigne);

    Livewire::actingAs($livreur)->test(Accueil::class)->call('basculer');
    expect($statut())->toBe(StatutLivreur::Disponible);
});

it('refuse le mode disponible à un livreur non validé', function () {
    $livreur = livreurValide(valide: false, statut: StatutLivreur::HorsLigne);

    Livewire::actingAs($livreur)
        ->test(Accueil::class)
        ->call('basculer')
        ->assertSet('erreur', 'Votre compte livreur n\'est pas encore validé.');
});

it('empêche de changer de mode pendant une course', function () {
    $livreur = livreurValide(statut: StatutLivreur::Occupe);

    Livewire::actingAs($livreur)
        ->test(Accueil::class)
        ->call('basculer')
        ->assertSet('erreur', 'Terminez votre course en cours avant de changer de mode.');
});
