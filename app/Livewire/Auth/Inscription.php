<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Actions\InscrireClientAction;
use App\Enums\ObjectifOtp;
use App\Livewire\Concerns\GereParcoursOtp;
use App\Models\Utilisateur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.invite')]
#[Title('Créer mon compte')]
class Inscription extends Component
{
    use GereParcoursOtp;

    public string $nom = '';

    protected function objectif(): ObjectifOtp
    {
        return ObjectifOtp::Inscription;
    }

    protected function avantEnvoi(): void
    {
        $this->validate(
            ['nom' => ['required', 'string', 'min:2', 'max:100']],
            [
                'nom.required' => 'Indiquez votre nom.',
                'nom.min' => 'Votre nom doit contenir au moins 2 caractères.',
                'nom.max' => 'Votre nom est trop long.',
            ],
        );
    }

    protected function terminer(): Utilisateur
    {
        return app(InscrireClientAction::class)->executer($this->nom, $this->telephone, $this->code);
    }

    public function render(): View
    {
        return view('livewire.auth.inscription');
    }
}
