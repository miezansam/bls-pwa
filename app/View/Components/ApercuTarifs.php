<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ApercuTarifs extends Component
{
    public ?int $prixMinimum;

    public function __construct()
    {
        $minimum = Zone::query()
            ->where('est_active', true)
            ->whereNotNull('frais_livraison')
            ->min('frais_livraison');

        $this->prixMinimum = $minimum === null ? null : (int) $minimum;
    }

    public function render(): View
    {
        return view('components.apercu-tarifs');
    }
}
