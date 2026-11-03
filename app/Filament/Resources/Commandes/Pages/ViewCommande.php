<?php

declare(strict_types=1);

namespace App\Filament\Resources\Commandes\Pages;

use App\Filament\Resources\Commandes\CommandeResource;
use Filament\Resources\Pages\ViewRecord;
use App\Actions\ChangerStatutCommandeAction;
use App\Enums\StatutCommande;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ViewCommande extends ViewRecord
{
    protected static string $resource = CommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('marquerLivree')
                ->label('Marquer comme livrée')
                ->color('warning')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => $this->record->statut === StatutCommande::EnRouteLivraison)
                ->modalHeading('Marquer cette commande comme livrée')
                ->modalDescription('À utiliser seulement si la livraison a vraiment eu lieu (par exemple code bloqué). Le client n\'aura pas validé le code. Le motif est conservé dans l\'historique.')
                ->modalSubmitActionLabel('Confirmer la livraison')
                ->schema([
                    Textarea::make('motif')
                        ->label('Motif')
                        ->required()
                        ->minLength(5)
                        ->maxLength(255),
                ])
                ->action(function (array $data, ChangerStatutCommandeAction $changer): void {
                    try {
                        $changer->executer(
                            $this->record,
                            StatutCommande::Livree,
                            auteur: auth()->user(),
                            note: 'Livraison confirmée par l\'admin : ' . $data['motif'],
                            codeLivraisonVerifie: true,
                        );
                    } catch (DomainException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()->title('Commande marquée comme livrée.')->success()->send();
                    $this->record->refresh();
                }),
        ];
    }
}
