<?php

declare(strict_types=1);

use App\Actions\ChangerStatutCommandeAction;
use App\Actions\ConfirmerLivraisonAction;
use App\Enums\StatutCommande;
use App\Enums\StatutLivreur;
use App\Enums\StatutPaiement;
use App\Exceptions\CodeLivraisonInvalideException;
use App\Exceptions\TransitionInvalideException;
use App\Models\Commande;
use App\Models\ProfilLivreur;
use App\Models\Utilisateur;

function profilDe(Utilisateur $livreur): ProfilLivreur
{
    return ProfilLivreur::query()->where('utilisateur_id', $livreur->id)->firstOrFail();
}

test('une course complète traverse tous les statuts, la caisse et l\'historique', function () {
    [$commande, $livreur] = courseEnRouteLivraison(800);

    expect(profilDe($livreur)->statut)->toBe(StatutLivreur::Occupe);

    $commande = app(ConfirmerLivraisonAction::class)->executer($commande, $commande->code_livraison, $livreur);

    expect($commande->statut)->toBe(StatutCommande::Livree);
    expect($commande->livree_le)->not->toBeNull();
    expect($commande->statut_paiement)->toBe(StatutPaiement::Paye);
    expect($commande->dernierPaiement->statut)->toBe(StatutPaiement::Paye);

    $profil = profilDe($livreur);
    expect($profil->solde_caisse)->toBe(800);
    expect($profil->statut)->toBe(StatutLivreur::Disponible);

    $parcours = $commande->historiqueStatuts->map(fn ($h) => $h->nouveau_statut->value)->all();
    expect($parcours)->toBe([
        'en_attente', 'acceptee', 'en_route_retrait', 'colis_recupere', 'en_route_livraison', 'livree',
    ]);
});

test('une transition interdite est refusée sans rien modifier', function () {
    $commande = commandeDeTest();

    expect(fn () => app(ChangerStatutCommandeAction::class)->executer($commande, StatutCommande::Livree))
        ->toThrow(TransitionInvalideException::class);

    expect($commande->fresh()->statut)->toBe(StatutCommande::EnAttente);
    expect($commande->fresh()->historiqueStatuts)->toHaveCount(1);
});

test('la livraison ne peut pas être validée sans vérification du code', function () {
    [$commande, $livreur] = courseEnRouteLivraison();

    expect(fn () => app(ChangerStatutCommandeAction::class)->executer($commande, StatutCommande::Livree, auteur: $livreur))
        ->toThrow(CodeLivraisonInvalideException::class);

    expect($commande->fresh()->statut)->toBe(StatutCommande::EnRouteLivraison);
});

test('un livreur peut se désister : la commande repasse en attente et il se libère', function () {
    $commande = commandeDeTest();
    $livreur = Utilisateur::factory()->livreur()->create();
    $changer = app(ChangerStatutCommandeAction::class);

    $commande = $changer->executer($commande, StatutCommande::Acceptee, auteur: $livreur, livreur: $livreur);
    expect(profilDe($livreur)->statut)->toBe(StatutLivreur::Occupe);

    $commande = $changer->executer($commande, StatutCommande::EnAttente, auteur: $livreur);

    expect($commande->statut)->toBe(StatutCommande::EnAttente);
    expect($commande->livreur_id)->toBeNull();
    expect($commande->acceptee_le)->toBeNull();
    expect(profilDe($livreur)->statut)->toBe(StatutLivreur::Disponible);
});

test('une annulation enregistre le motif, l\'auteur et clôt le paiement en attente', function () {
    $commande = commandeDeTest();
    $client = $commande->client;

    $commande = app(ChangerStatutCommandeAction::class)
        ->executer($commande, StatutCommande::Annulee, auteur: $client, note: 'Changement de plan');

    expect($commande->statut)->toBe(StatutCommande::Annulee);
    expect($commande->motif_annulation)->toBe('Changement de plan');
    expect($commande->annulee_par)->toBe($client->id);
    expect($commande->annulee_le)->not->toBeNull();
    expect($commande->statut_paiement)->toBe(StatutPaiement::Echoue);
    expect($commande->dernierPaiement->statut)->toBe(StatutPaiement::Echoue);
});

test('accepter une commande sans livreur est une erreur', function () {
    $commande = commandeDeTest();

    expect(fn () => app(ChangerStatutCommandeAction::class)->executer($commande, StatutCommande::Acceptee))
        ->toThrow(InvalidArgumentException::class);
});

test('un mauvais code est compté, puis la livraison est bloquée après 5 essais', function () {
    [$commande, $livreur] = courseEnRouteLivraison();
    $mauvaisCode = $commande->code_livraison === '9999' ? '0000' : '9999';
    $confirmer = app(ConfirmerLivraisonAction::class);

    for ($i = 1; $i <= 5; $i++) {
        expect(fn () => $confirmer->executer($commande, $mauvaisCode, $livreur))
            ->toThrow(CodeLivraisonInvalideException::class);
        expect(Commande::find($commande->id)->code_livraison_tentatives)->toBe($i);
    }

    // Même avec le bon code, la livraison est maintenant bloquée
    expect(fn () => $confirmer->executer($commande, $commande->code_livraison, $livreur))
        ->toThrow(CodeLivraisonInvalideException::class);

    expect(Commande::find($commande->id)->statut)->toBe(StatutCommande::EnRouteLivraison);
});

test('un autre livreur ne peut pas confirmer la livraison', function () {
    [$commande] = courseEnRouteLivraison();
    $intrus = Utilisateur::factory()->livreur()->create();

    expect(fn () => app(ConfirmerLivraisonAction::class)->executer($commande, $commande->code_livraison, $intrus))
        ->toThrow(DomainException::class);
});
