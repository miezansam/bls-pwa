<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Écrit les SMS dans les logs : pour le développement uniquement. */
final class EnvoyeurSmsJournal implements EnvoyeurSms
{
    public function envoyer(string $telephone, string $message): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'Aucun fournisseur SMS configuré : le mode « journal » est réservé au développement.'
            );
        }

        Log::info("SMS vers {$telephone} : {$message}");
    }
}
