<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReversementsCaisse\Pages;

use App\Filament\Resources\ReversementsCaisse\ReversementCaisseResource;
use Filament\Resources\Pages\ListRecords;

class ListReversementsCaisse extends ListRecords
{
    protected static string $resource = ReversementCaisseResource::class;
}
