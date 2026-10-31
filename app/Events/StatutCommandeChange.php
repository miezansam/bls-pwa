<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\StatutCommande;
use App\Models\Commande;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StatutCommandeChange implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Commande $commande,
        public readonly ?StatutCommande $ancienStatut,
        public readonly StatutCommande $nouveauStatut,
    ) {
    }
}
