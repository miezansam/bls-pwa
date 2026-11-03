<?php

declare(strict_types=1);

use App\Enums\StatutCommande;
use App\Filament\Resources\Commandes\Pages\ViewCommande;
use App\Livewire\Livreur\Accueil;
use App\Models\Commande;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;
use Database\Seeders\TarifsOfficielsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(TarifsOfficielsSeeder::class);
    $this->travelTo('2026-11-04 12:00:00');
});

it('permet à l\'admin de clôturer une livraison bloquée et crédite la caisse', function () {
    $livreur = livreurValide();
    $offre = offrePour($livreur);
    Livewire::actingAs($livreur)->test(Accueil::class)->call('accepter', $offre->id);

    $commande = Commande::query()->findOrFail($offre->commande_id);
    $commande->update(['statut' => StatutCommande::EnRouteLivraison, 'code_livraison_tentatives' => 99]);

    Livewire::actingAs(Utilisateur::factory()->admin()->create())
        ->test(ViewCommande::class, ['record' => $commande->getKey()])
        ->callAction('marquerLivree', ['motif' => 'Code bloqué, client confirmé par téléphone'])
        ->assertNotified();

    $profil = ProfilLivreur::query()->where('utilisateur_id', $livreur->id)->firstOrFail();

    expect($commande->refresh()->statut)->toBe(StatutCommande::Livree)
        ->and($commande->historiqueStatuts()->get()->last()->note)->toContain('Code bloqué')
        ->and($profil->solde_caisse)->toBe(500);
});

it('n\'affiche pas l\'action sur une commande pas encore en livraison', function () {
    $commande = offrePour(livreurValide())->commande;

    Livewire::actingAs(Utilisateur::factory()->admin()->create())
        ->test(ViewCommande::class, ['record' => $commande->getKey()])
        ->assertActionHidden('marquerLivree');
});
