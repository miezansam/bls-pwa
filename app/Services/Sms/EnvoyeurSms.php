<?php

declare(strict_types=1);

namespace App\Services\Sms;

interface EnvoyeurSms
{
    /** Envoie un SMS. Lance une exception si l'envoi échoue. */
    public function envoyer(string $telephone, string $message): void;
}
