<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonInterface;

/** Ce que le client a choisi (ou ce que l'heure impose) et qui peut modifier le prix. */
final readonly class OptionsCommande
{
    public function __construct(
        public bool $express = false,
        public bool $doubleCourse = false,
        public bool $speciale = false,
        public int $nombreRepas = 1,
        public ?CarbonInterface $quand = null,
    ) {
    }
}
