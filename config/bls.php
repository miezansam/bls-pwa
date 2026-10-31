<?php

declare(strict_types=1);

return [
    // Les frais de livraison sont arrondis au multiple supérieur de cette valeur (FCFA)
    'arrondi_fcfa' => 50,

    // Estimation du trajet routier : distance à vol d'oiseau multipliée par ce facteur
    'facteur_routier' => 1.3,

    // Vitesse moyenne en ville (km/h), pour l'estimation de la durée
    'vitesse_moyenne_kmh' => 20,

    // Le paiement Mobile Money arrive en V2
    'mobile_money_actif' => false,

    'code_livraison_max_tentatives' => 5,

    // Délai laissé à un livreur pour répondre à une offre de course
    'delai_reponse_attribution_minutes' => 5,
];
