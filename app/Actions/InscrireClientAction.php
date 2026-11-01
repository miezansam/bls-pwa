<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RoleUtilisateur;
use App\Exceptions\AuthentificationException;
use App\Models\Utilisateur;
use App\Services\ComptesService;
use App\Services\OtpService;
use App\Support\Telephone;
use Illuminate\Database\UniqueConstraintViolationException;
use Spatie\Permission\Models\Role;

final class InscrireClientAction
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly ComptesService $comptes,
    ) {
    }

    public function executer(string $nom, string $saisieTelephone, string $code): Utilisateur
    {
        $nom = trim((string) preg_replace('/\s+/u', ' ', $nom));

        // Validé avant la vérification du code : une faute de saisie ne consomme ni le code ni un essai
        if (mb_strlen($nom) < 2 || mb_strlen($nom) > 100) {
            throw AuthentificationException::nomInvalide();
        }

        $telephone = Telephone::normaliser($saisieTelephone)
            ?? throw AuthentificationException::telephoneInvalide();

        return $this->otp->consommer($telephone, $code, function () use ($nom, $telephone): Utilisateur {
            $this->comptes->verifierNumeroLibre($telephone);

            try {
                $client = Utilisateur::create([
                    'nom' => $nom,
                    'telephone' => $telephone,
                    'telephone_verifie_le' => now(),
                    'derniere_connexion_le' => now(),
                    'est_actif' => true,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Deux inscriptions simultanées avec le même numéro
                throw AuthentificationException::compteExistant();
            }

            Role::findOrCreate(RoleUtilisateur::Client->value, 'web');
            $client->assignRole(RoleUtilisateur::Client->value);

            return $client;
        });
    }
}
