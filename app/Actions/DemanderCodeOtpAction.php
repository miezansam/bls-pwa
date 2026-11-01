<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ObjectifOtp;
use App\Exceptions\AuthentificationException;
use App\Services\ComptesService;
use App\Services\OtpService;
use App\Support\Telephone;

final class DemanderCodeOtpAction
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly ComptesService $comptes,
    ) {
    }

    /** Retourne le numéro normalisé, à réutiliser à l'étape suivante. */
    public function executer(string $saisieTelephone, ObjectifOtp $objectif, ?string $adresseIp = null): string
    {
        $telephone = Telephone::normaliser($saisieTelephone)
            ?? throw AuthentificationException::telephoneInvalide();

        // On vérifie avant d'envoyer : un SMS coûte de l'argent
        match ($objectif) {
            ObjectifOtp::Connexion => $this->comptes->trouverPourConnexion($telephone),
            ObjectifOtp::Inscription => $this->comptes->verifierNumeroLibre($telephone),
        };

        $this->otp->envoyer($telephone, $adresseIp);

        return $telephone;
    }
}
