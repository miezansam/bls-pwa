<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RegleOption: string implements HasLabel
{
    case Minimum = 'minimum';
    case Supplement = 'supplement';

    public function libelle(): string
    {
        return match ($this) {
            self::Minimum => 'Prix minimum',
            self::Supplement => 'Supplément',
        };
    }

    public function getLabel(): string
    {
        return $this->libelle();
    }
}
