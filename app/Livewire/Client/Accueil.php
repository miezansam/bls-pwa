<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Models\Commande;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.espace')]
#[Title('Espace client')]
class Accueil extends Component
{
    #[Computed]
    public function commandeEnCours(): ?Commande
    {
        return Commande::query()
            ->where('client_id', auth()->id())
            ->enCours()
            ->latest()
            ->first();
    }

    public function render(): View
    {
        return view('livewire.client.accueil');
    }
}
