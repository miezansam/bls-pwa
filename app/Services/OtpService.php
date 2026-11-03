<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthentificationException;
use App\Models\CodeOtp;
use App\Services\Sms\EnvoyeurSms;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

final class OtpService
{
    public function __construct(private readonly EnvoyeurSms $sms)
    {
    }

    /** Émet un code et l'envoie par SMS. Le numéro doit déjà être normalisé (+225...). */
    public function envoyer(string $telephone, ?string $adresseIp = null): void
    {
        // Verrou : deux demandes simultanées pour le même numéro sont traitées l'une après l'autre
        Cache::lock("otp:{$telephone}", 10)->block(5, function () use ($telephone, $adresseIp): void {
            $this->verifierLimites($telephone);

            // Un nouveau code annule les précédents
            CodeOtp::query()->where('telephone', $telephone)->whereNull('utilise_le')->update(['utilise_le' => now()]);

            $longueur = (int) config('bls.otp.longueur');
            $minutes = (int) config('bls.otp.duree_minutes');
            $code = str_pad((string) random_int(0, 10 ** $longueur - 1), $longueur, '0', STR_PAD_LEFT);

            $otp = CodeOtp::create([
                'telephone' => $telephone,
                'code_hash' => $this->empreinte($telephone, $code),
                'tentatives' => 0,
                'adresse_ip' => $adresseIp,
                'expire_le' => now()->addMinutes($minutes),
            ]);

            try {
                // Message sans accent : reste dans un seul SMS (alphabet GSM, 160 caractères)
                $this->sms->envoyer($telephone, sprintf(
                    "BLS : votre code est {$code}. Valable 5 minutes. Ne le partagez avec personne."
                ));
            } catch (Throwable $e) {
                // L'échec n'est pas la faute de l'utilisateur : on ne compte pas ce code dans ses limites
                $otp->delete();
                report($e);

                throw AuthentificationException::smsIndisponible();
            }
        });
    }

    /**
     * Vérifie le code, le marque comme utilisé, puis exécute $quandValide dans la même transaction.
     * Si $quandValide échoue, le code reste utilisable.
     *
     * @return mixed Le résultat de $quandValide
     */
    public function consommer(string $telephone, string $code, Closure $quandValide): mixed
    {
        $code = (string) preg_replace('/\D/', '', $code);
        $echec = null;
        $resultat = null;

        // L'incrémentation des essais doit être validée AVANT de lancer l'exception, sinon la transaction
        // l'annulerait : on garde donc l'échec de côté et on le lance après la transaction.
        DB::transaction(function () use ($telephone, $code, $quandValide, &$echec, &$resultat): void {
            $otp = CodeOtp::query()
                ->where('telephone', $telephone)
                ->whereNull('utilise_le')
                ->where('expire_le', '>', now())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($otp === null) {
                $echec = AuthentificationException::codeExpire();

                return;
            }

            $maximum = (int) config('bls.otp.tentatives_max');

            if ($otp->tentatives >= $maximum) {
                $echec = AuthentificationException::codeBloque();

                return;
            }

            if (! hash_equals($otp->code_hash, $this->empreinte($telephone, $code))) {
                $otp->increment('tentatives');
                $echec = AuthentificationException::codeIncorrect($maximum - $otp->tentatives);

                return;
            }

            $otp->update(['utilise_le' => now()]);
            $resultat = $quandValide();
        });

        if ($echec !== null) {
            throw $echec;
        }

        return $resultat;
    }

    private function verifierLimites(string $telephone): void
    {
        $derniere = CodeOtp::query()->where('telephone', $telephone)->latest('id')->first();

        if ($derniere !== null) {
            $restant = $derniere->created_at->getTimestamp()
                + (int) config('bls.otp.delai_renvoi_secondes')
                - now()->getTimestamp();

            if ($restant > 0) {
                throw AuthentificationException::tropDeDemandes($restant);
            }
        }

        $recents = CodeOtp::query()
            ->where('telephone', $telephone)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($recents >= (int) config('bls.otp.codes_max_par_heure')) {
            throw AuthentificationException::limiteHoraire();
        }
    }

    /** HMAC avec la clé de l'application : même volé, le contenu de la base ne permet pas de retrouver les codes. */
    private function empreinte(string $telephone, string $code): string
    {
        return hash_hmac('sha256', $telephone . '|' . $code, (string) config('app.key'));
    }
}
