<?php

declare(strict_types=1);

namespace App\Data;

final readonly class EstimationPrix
{
    public function __construct(
        public int $fraisLivraison,
        public int $distanceM,
        public int $dureeEstimeeS,
        public int $tarifId,
    ) {
    }
}
