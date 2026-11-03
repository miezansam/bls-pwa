<?php

declare(strict_types=1);

use App\Enums\StatutCommande;
use App\Enums\StatutLivreur;
use App\Enums\TypeCommande;
use App\Livewire\Livreur\Accueil;
use App\Models\Commande;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;
use Database\Seeders\TarifsOfficielsSeeder;
use Livewire\Livewire;

function courseAcceptee(Utilisateur $livreur, bool $repas = false): Commande
{
    $offre = offrePour($livreur);

    if ($repas) {
        $offre->commande->update(['type' => TypeCommande::Repas]);
    }

    Livewire::actingAs($livreur)->test(Accueil::class)->call('accepter', $offre->id);

    return Commande::query()->findOrFail($offre->commande_id);
}

beforeEach(function () {
    $this->seed(TarifsOfficielsSeeder::class);
    $this->travelTo('2026-11-04 12:00:00');
});

it('mène un colis de l\'acceptation à la livraison avec le bon code', function () {
    $livreur = livreurValide();
    $commande = courseAcceptee($livreur);

    $page = Livewire::actingAs($livreur)->test(Accueil::class);
    $page->call('avancer')->call('avancer')->call('avancer');

    expect($commande->refresh()->statut)->toBe(StatutCommande::EnRouteLivraison);

    $page->set('codeLivraison', $commande->code_livraison)
        ->call('confirmerLivraison')
        ->assertSee('500 FCFA');

    $profil = ProfilLivreur::query()->where('utilisateur_id', $livreur->id)->firstOrFail();

    expect($commande->refresh()->statut)->toBe(StatutCommande::Livree)
        ->and($profil->solde_caisse)->toBe(500)
        ->and($profil->statut)->toBe(StatutLivreur::Disponible);
});

it('refuse un mauvais code de livraison et compte la tentative', function () {
    $livreur = livreurValide();
    $commande = courseAcceptee($livreur);
    $commande->update(['code_livraison' => '4821', 'statut' => StatutCommande::EnRouteLivraison]);

    Livewire::actingAs($livreur)
        ->test(Accueil::class)
        ->set('codeLivraison', '1111')
        ->call('confirmerLivraison')
        ->assertNotSet('erreur', '');

    expect($commande->refresh()->statut)->toBe(StatutCommande::EnRouteLivraison)
        ->and($commande->code_livraison_tentatives)->toBe(1);
});

it('exige le montant des articles pour un repas, puis met à jour le total', function () {
    $livreur = livreurValide();
    $commande = courseAcceptee($livreur, repas: true);

    $page = Livewire::actingAs($livreur)->test(Accueil::class)->call('avancer');

    $page->call('avancer')->assertHasErrors('montantArticles');
    expect($commande->refresh()->statut)->toBe(StatutCommande::EnRouteRetrait);

    $page->set('montantArticles', '2 500')->call('avancer')->assertHasNoErrors();

    $commande->refresh();

    expect($commande->statut)->toBe(StatutCommande::ColisRecupere)
        ->and($commande->montant_articles)->toBe(2500)
        ->and($commande->montant_total)->toBe(3000)
        ->and($commande->paiements()->firstOrFail()->montant)->toEqual(3000);
});

it('n\'expose jamais le code de livraison au livreur', function () {
    $livreur = livreurValide();
    $commande = courseAcceptee($livreur);
    $commande->update(['code_livraison' => '7391']);

    Livewire::actingAs($livreur)->test(Accueil::class)->assertDontSee('7391');
});
