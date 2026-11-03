<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Models\Commande;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.espace')]
#[Title('Ma commande')]
class SuiviCommande extends Component
{
    public Commande $commande;

    public function mount(Commande $commande): void
    {
        abort_unless($commande->client_id === auth()->id(), 404);

        $this->commande = $commande;
    }

    public function render()
    {
        // Rafraîchissement provisoire, remplacé par Reverb à l'étape 4.
        $this->commande->refresh();

        return view('livewire.client.suivi-commande');
    }
}
