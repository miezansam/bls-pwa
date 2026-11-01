<?php

declare(strict_types=1);

namespace App\Filament\Resources\Commandes;

use App\Filament\Resources\Commandes\Pages\ListCommandes;
use App\Filament\Resources\Commandes\Pages\ViewCommande;
use App\Filament\Resources\Commandes\Tables\CommandesTable;
use App\Models\Commande;
use App\Support\Format;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CommandeResource extends Resource
{
    protected static ?int $navigationSort = 1;

    protected static ?string $model = Commande::class;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getModelLabel(): string
    {
        return 'commande';
    }

    public static function getPluralModelLabel(): string
    {
        return 'commandes';
    }

    /** Les commandes sont créées par les clients, jamais depuis l'admin. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Commande')
                ->schema([
                    TextEntry::make('reference')->label('Référence'),
                    TextEntry::make('type')->label('Type')->badge(),
                    TextEntry::make('statut')->label('Statut')->badge(),
                    TextEntry::make('created_at')->label('Créée le')->dateTime('d/m/Y H:i'),
                    TextEntry::make('description')->label('Description')->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Personnes')
                ->schema([
                    TextEntry::make('client.nom')->label('Client'),
                    TextEntry::make('client.telephone')->label('Téléphone du client'),
                    TextEntry::make('livreur.nom')->label('Livreur')->placeholder('Pas encore attribuée'),
                    TextEntry::make('attribuee_le')->label('Dernière offre envoyée')->dateTime('d/m/Y H:i')->placeholder('Aucune'),
                ])
                ->columns(2),

            Section::make('Retrait')
                ->schema([
                    TextEntry::make('zoneRetrait.nom')->label('Zone')->placeholder('Non précisée'),
                    TextEntry::make('retrait_repere')->label('Repère'),
                    TextEntry::make('retrait_contact_nom')->label('Contact')->placeholder('Non précisé'),
                    TextEntry::make('retrait_contact_telephone')->label('Téléphone à appeler'),
                ])
                ->columns(2),

            Section::make('Livraison')
                ->schema([
                    TextEntry::make('zoneLivraison.nom')->label('Zone')->placeholder('Non précisée'),
                    TextEntry::make('livraison_repere')->label('Repère'),
                    TextEntry::make('livraison_contact_nom')->label('Contact')->placeholder('Non précisé'),
                    TextEntry::make('livraison_contact_telephone')->label('Téléphone à appeler'),
                ])
                ->columns(2),

            Section::make('Montants et paiement')
                ->schema([
                    TextEntry::make('frais_livraison')->label('Frais de livraison')->formatStateUsing(fn ($state): string => Format::fcfa($state)),
                    TextEntry::make('montant_articles')->label('Articles avancés')->formatStateUsing(fn ($state): string => Format::fcfa($state)),
                    TextEntry::make('montant_total')->label('Total à payer')->formatStateUsing(fn ($state): string => Format::fcfa($state)),
                    TextEntry::make('distance_m')->label('Distance estimée')->formatStateUsing(fn ($state): ?string => Format::kilometres($state))->placeholder('Inconnue'),
                    TextEntry::make('mode_paiement')->label('Mode de paiement')->badge(),
                    TextEntry::make('statut_paiement')->label('Statut du paiement')->badge(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return CommandesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommandes::route('/'),
            'view' => ViewCommande::route('/{record}'),
        ];
    }
}
