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

    // Compte administrateur créé automatiquement en LOCAL par le seeder de démonstration.
    // Les valeurs viennent du .env (jamais versionné).
    'dev_admin' => [
        'email' => env('DEV_ADMIN_EMAIL'),
        'mot_de_passe' => env('DEV_ADMIN_PASSWORD'),
    ],

    // Connexion par code SMS (OTP)
    'otp' => [
        'longueur' => 6,
        'duree_minutes' => 5,
        'tentatives_max' => 5,
        'delai_renvoi_secondes' => 60,
        'codes_max_par_heure' => 5,
        'ip_demandes_max_par_heure' => 10,
        'ip_verifications_max_par_heure' => 30,
    ],

    // Envoi des SMS. « journal » écrit dans les logs : réservé au développement.
    'sms' => [
        'driver' => env('SMS_DRIVER', 'journal'),
    ],

    'couleurs' => [
        'marine' => '#011F48',
        'ambre' => '#F1AE06',
    ],
];
