<?php

declare(strict_types=1);

namespace App\Filament\Resources\OptionsTarifaires\Pages;

use App\Filament\Resources\OptionsTarifaires\OptionTarifaireResource;
use Filament\Resources\Pages\ManageRecords;

class ManageOptionsTarifaires extends ManageRecords
{
    protected static string $resource = OptionTarifaireResource::class;
}
