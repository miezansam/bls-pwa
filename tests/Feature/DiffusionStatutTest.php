<?php

declare(strict_types=1);

use App\Enums\StatutCommande;
use App\Events\StatutCommandeChange;
use Database\Seeders\TarifsOfficielsSeeder;
use App\Actions\AttribuerCommandeAction;
use App\Events\OffreProposee;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Event;

it('diffuse le statut sur le canal privé de la commande sans le code de livraison', function () {
    $this->seed(TarifsOfficielsSeeder::class);
    $commande = offrePour(livreurValide())->commande;

    $evenement = new StatutCommandeChange($commande, StatutCommande::EnAttente, StatutCommande::Acceptee);

    expect($evenement->broadcastWith())->toBe([
        'reference' => $commande->reference,
        'statut' => 'acceptee',
        'libelle' => 'Acceptée',
    ])->and($evenement->broadcastOn()[0]->name)->toBe('private-commande.' . $commande->id);
});
it('annonce l\'offre au livreur sur son canal privé, sans détail de la commande', function () {
    $this->seed(TarifsOfficielsSeeder::class);
    $livreur = livreurValide();
    $offre = offrePour($livreur);

    $evenement = new OffreProposee($offre);

    expect($evenement->broadcastOn()[0]->name)->toBe('private-livreur.' . $livreur->id)
        ->and($evenement->broadcastWith())->toBe(['reference' => $offre->commande->reference]);
});

it('diffuse l\'offre quand l\'admin attribue une commande', function () {
    $this->seed(TarifsOfficielsSeeder::class);
    Event::fake([OffreProposee::class]);

    $livreur = livreurValide();
    $commande = offrePour(livreurValide())->commande;

    app(AttribuerCommandeAction::class)->executer($commande, $livreur, Utilisateur::factory()->admin()->create());

    Event::assertDispatched(OffreProposee::class, fn (OffreProposee $e): bool => $e->attribution->livreur_id === $livreur->id);
});
