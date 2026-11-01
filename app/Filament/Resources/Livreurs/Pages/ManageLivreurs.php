<?php

declare(strict_types=1);

namespace App\Filament\Resources\Livreurs\Pages;

use App\Actions\CreerLivreurAction;
use App\Filament\Resources\Livreurs\LivreurResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageLivreurs extends ManageRecords
{
    protected static string $resource = LivreurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nouveau livreur')
                ->createAnother(false)
                // Crée le compte, le rôle et le profil ensemble (pas un simple create sur ProfilLivreur)
                ->using(fn (array $data): Model => app(CreerLivreurAction::class)->executer($data)),
        ];
    }
}
