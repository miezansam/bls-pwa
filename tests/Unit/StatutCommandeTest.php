<?php

declare(strict_types=1);

use App\Enums\StatutCommande;

test('le parcours nominal d\'une course est autorisé étape par étape', function () {
    $parcours = [
        StatutCommande::EnAttente,
        StatutCommande::Acceptee,
        StatutCommande::EnRouteRetrait,
        StatutCommande::ColisRecupere,
        StatutCommande::EnRouteLivraison,
        StatutCommande::Livree,
    ];

    foreach ($parcours as $i => $statut) {
        if (isset($parcours[$i + 1])) {
            expect($statut->peutPasserA($parcours[$i + 1]))->toBeTrue();
        }
    }
});

test('on ne peut pas sauter une étape', function () {
    expect(StatutCommande::EnAttente->peutPasserA(StatutCommande::Livree))->toBeFalse();
    expect(StatutCommande::Acceptee->peutPasserA(StatutCommande::EnRouteLivraison))->toBeFalse();
});

test('aucune sortie possible depuis un statut terminal', function () {
    foreach ([StatutCommande::Livree, StatutCommande::Annulee] as $statut) {
        expect($statut->estTerminal())->toBeTrue();
        expect($statut->transitionsAutorisees())->toBeEmpty();
    }
});

test('l\'annulation n\'est plus possible une fois le colis récupéré', function () {
    expect(StatutCommande::ColisRecupere->peutPasserA(StatutCommande::Annulee))->toBeFalse();
    expect(StatutCommande::EnRouteLivraison->peutPasserA(StatutCommande::Annulee))->toBeFalse();
});

test('un livreur peut se désister avant d\'aller au retrait', function () {
    expect(StatutCommande::Acceptee->peutPasserA(StatutCommande::EnAttente))->toBeTrue();
});

test('le suivi GPS ne se déclenche que pendant une course active', function () {
    expect(StatutCommande::EnAttente->declencheSuiviGps())->toBeFalse();
    expect(StatutCommande::EnRouteLivraison->declencheSuiviGps())->toBeTrue();
    expect(StatutCommande::Livree->declencheSuiviGps())->toBeFalse();
});
