<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\AuthentificationException;
use App\Models\Utilisateur;
use App\Services\ComptesService;
use App\Services\OtpService;
use App\Support\Telephone;

final class ConnecterParOtpAction
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly ComptesService $comptes,
    ) {
    }

    /** Vérifie le code et retourne l'utilisateur. L'ouverture de la session est faite par l'écran. */
    public function executer(string $saisieTelephone, string $code): Utilisateur
    {
        $telephone = Telephone::normaliser($saisieTelephone)
            ?? throw AuthentificationException::telephoneInvalide();

        return $this->otp->consommer($telephone, $code, function () use ($telephone): Utilisateur {
            // Le compte a pu être désactivé entre la demande du code et sa saisie
            $utilisateur = $this->comptes->trouverPourConnexion($telephone, verrou: true);

            $utilisateur->update([
                'telephone_verifie_le' => $utilisateur->telephone_verifie_le ?? now(),
                'derniere_connexion_le' => now(),
            ]);

            return $utilisateur;
        });
    }
}
