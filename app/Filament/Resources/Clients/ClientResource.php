<?php

declare(strict_types=1);

namespace App\Filament\Resources\Clients;

use App\Enums\RoleUtilisateur;
use App\Filament\Actions\ActionsCompte;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Models\Utilisateur;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = Utilisateur::class;

    protected static ?string $slug = 'clients';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'client';
    }

    public static function getPluralModelLabel(): string
    {
        return 'clients';
    }

    /** Les clients sont créés par leur propre inscription (OTP), jamais depuis l'admin. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roles', fn (Builder $q) => $q->where('name', RoleUtilisateur::Client->value));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('nom')->label('Client')->searchable(),
                TextColumn::make('telephone')->label('Téléphone')->searchable(),
                TextColumn::make('commandes_count')->label('Commandes')->counts('commandes')->sortable(),
                IconColumn::make('est_actif')->label('Compte actif')->boolean(),
                TextColumn::make('created_at')->label('Inscrit le')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                Filter::make('suspendus')
                    ->label('Comptes désactivés')
                    ->query(fn (Builder $query): Builder => $query->where('est_actif', false)),
            ])
            ->recordActions([
                ActionsCompte::desactiver(),
                ActionsCompte::reactiver(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
        ];
    }
}
