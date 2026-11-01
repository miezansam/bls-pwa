<?php

declare(strict_types=1);

namespace App\Data;

final readonly class EstimationPrix
{
    /** @param array<string, mixed> $detail Explication du prix, conservée dans la commande */
    public function __construct(
        public int $fraisLivraison,
        public int $distanceM,
        public int $dureeEstimeeS,
        public ?int $tarifId,
        public array $detail = [],
    ) {
    }
}
