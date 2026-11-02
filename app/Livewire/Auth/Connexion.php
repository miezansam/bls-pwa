<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Actions\ConnecterParOtpAction;
use App\Enums\ObjectifOtp;
use App\Livewire\Concerns\GereParcoursOtp;
use App\Models\Utilisateur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.invite')]
#[Title('Connexion')]
class Connexion extends Component
{
    use GereParcoursOtp;

    protected function objectif(): ObjectifOtp
    {
        return ObjectifOtp::Connexion;
    }

    protected function terminer(): Utilisateur
    {
        return app(ConnecterParOtpAction::class)->executer($this->telephone, $this->code);
    }

    public function render(): View
    {
        return view('livewire.auth.connexion');
    }
}
