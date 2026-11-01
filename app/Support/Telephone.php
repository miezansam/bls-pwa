<?php

declare(strict_types=1);

namespace App\Support;

final class Telephone
{
    /**
     * Numéro mobile ivoirien à 10 chiffres, saisi librement.
     * Retourne le format international (+2250700000000) ou null si le numéro est invalide.
     */
    public static function normaliser(string $saisie): ?string
    {
        $nettoye = preg_replace('/[^\d+]/', '', trim($saisie)) ?? '';

        if (str_starts_with($nettoye, '00')) {
            $nettoye = '+' . substr($nettoye, 2);
        }

        if (str_starts_with($nettoye, '+')) {
            return preg_match('/^\+225\d{10}$/', $nettoye) === 1 ? $nettoye : null;
        }

        if (preg_match('/^225\d{10}$/', $nettoye) === 1) {
            return '+' . $nettoye;
        }

        return preg_match('/^\d{10}$/', $nettoye) === 1 ? '+225' . $nettoye : null;
    }

    /** Présentation lisible : +225 07 00 00 00 00 */
    public static function formater(string $telephone): string
    {
        return preg_match('/^\+225(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $telephone, $m) === 1
            ? "+225 {$m[1]} {$m[2]} {$m[3]} {$m[4]} {$m[5]}"
            : $telephone;
    }
}
