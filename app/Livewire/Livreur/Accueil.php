<?php

declare(strict_types=1);

namespace App\Livewire\Livreur;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.espace')]
#[Title('Espace livreur')]
class Accueil extends Component
{
    public function render(): View
    {
        return view('livewire.livreur.accueil');
    }
}
