<?php

declare(strict_types=1);

namespace App\Support;

final class Format
{
    public static function fcfa(int|string|null $montant): string
    {
        return number_format((int) $montant, 0, ',', ' ') . ' FCFA';
    }

    public static function kilometres(?int $metres): ?string
    {
        return $metres === null ? null : number_format($metres / 1000, 1, ',', ' ') . ' km';
    }
}
