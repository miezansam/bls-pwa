<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Actions\DemanderCodeOtpAction;
use App\Enums\ObjectifOtp;
use App\Exceptions\AuthentificationException;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/** Parcours « numéro, puis code SMS, puis session ouverte », commun à la connexion et à l'inscription. */
trait GereParcoursOtp
{
    public string $etape = 'telephone';   // 'telephone' ou 'code'

    public string $telephone = '';

    public string $code = '';

    public int $delaiRenvoi = 0;          // secondes avant de pouvoir redemander un code (affichage)

    public int $envois = 0;               // change à chaque envoi pour relancer le compte à rebours

    abstract protected function objectif(): ObjectifOtp;

    /** Vérifie le code et retourne l'utilisateur concerné. */
    abstract protected function terminer(): Utilisateur;

    /** Contrôles propres à l'écran avant l'envoi du code. */
    protected function avantEnvoi(): void
    {
    }

    public function demanderCode(): void
    {
        $this->resetErrorBag();
        $this->avantEnvoi();

        if ($this->limiteParIpAtteinte('otp-demande', 'ip_demandes_max_par_heure', 'telephone')) {
            return;
        }

        try {
            $this->telephone = app(DemanderCodeOtpAction::class)
                ->executer($this->telephone, $this->objectif(), request()->ip());
        } catch (AuthentificationException $e) {
            $this->addError('telephone', $e->getMessage());

            return;
        }

        $this->etape = 'code';
        $this->code = '';
        $this->delaiRenvoi = (int) config('bls.otp.delai_renvoi_secondes');
        $this->envois++;
    }

    public function renvoyerCode(): void
    {
        $this->demanderCode();
    }

    public function modifierNumero(): void
    {
        $this->resetErrorBag();
        $this->etape = 'telephone';
        $this->code = '';
    }

    public function verifierCode(): void
    {
        $this->resetErrorBag();
        $this->code = (string) preg_replace('/\D/', '', $this->code);

        $this->validate(
            ['code' => ['required', 'digits:' . (int) config('bls.otp.longueur')]],
            [
                'code.required' => 'Entrez le code reçu par SMS.',
                'code.digits' => 'Le code contient :digits chiffres.',
            ],
        );

        if ($this->limiteParIpAtteinte('otp-verification', 'ip_verifications_max_par_heure', 'code')) {
            return;
        }

        try {
            $utilisateur = $this->terminer();
        } catch (AuthentificationException $e) {
            $this->addError('code', $e->getMessage());

            return;
        }

        Auth::login($utilisateur, remember: true);
        session()->regenerate();

        $this->redirectIntended(default: route('accueil'));
    }

    private function limiteParIpAtteinte(string $nom, string $reglage, string $champErreur): bool
    {
        $cle = $nom . ':' . request()->ip();

        if (RateLimiter::tooManyAttempts($cle, (int) config("bls.otp.{$reglage}"))) {
            $minutes = (int) ceil(RateLimiter::availableIn($cle) / 60);

            $this->addError($champErreur, "Trop de tentatives depuis cet appareil. Réessayez dans {$minutes} minute(s).");

            return true;
        }

        RateLimiter::hit($cle, 3600);

        return false;
    }
}
