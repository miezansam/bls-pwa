<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tarifs\Pages;

use App\Filament\Resources\Tarifs\TarifResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTarifs extends ManageRecords
{
    protected static string $resource = TarifResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
