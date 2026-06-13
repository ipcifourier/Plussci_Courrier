<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class DocumentAccessRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'accessRules';

    protected static ?string $title = 'Droits d\'accès';

    public function isReadOnly(): bool
    {
        return false;
    }

    // ── Form ─────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Forms\Components\Select::make('user_id')
                ->label('Utilisateur')
                ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->helperText('Laisser vide si la règle s\'applique à un rôle.'),

            Forms\Components\Select::make('role_id')
                ->label('Rôle')
                ->options(fn () => Role::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->helperText('Laisser vide si la règle s\'applique à un utilisateur.'),

            Forms\Components\Toggle::make('can_view')
                ->label('Peut consulter')
                ->default(true)
                ->inline(false),

            Forms\Components\Toggle::make('can_download')
                ->label('Peut télécharger')
                ->default(false)
                ->inline(false),

            Forms\Components\Toggle::make('can_edit')
                ->label('Peut modifier')
                ->default(false)
                ->inline(false),

            Forms\Components\Toggle::make('can_share')
                ->label('Peut partager')
                ->default(false)
                ->inline(false),

        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->heading('Règles d\'accès au document')
            ->description('Définissez qui peut consulter, modifier ou télécharger ce document.')
            ->emptyStateHeading('Aucune règle définie')
            ->emptyStateDescription('Sans règle, l\'accès est déterminé uniquement par les permissions globales du rôle.')
            ->emptyStateIcon('heroicon-o-shield-exclamation')

            ->columns([
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->placeholder('— (rôle)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role.name')
                    ->label('Rôle')
                    ->placeholder('— (utilisateur)')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('can_view')
                    ->label('Consulter')
                    ->boolean()
                    ->trueIcon('heroicon-m-eye')
                    ->falseIcon('heroicon-m-eye-slash'),

                IconColumn::make('can_download')
                    ->label('Télécharger')
                    ->boolean()
                    ->trueIcon('heroicon-m-arrow-down-tray')
                    ->falseIcon('heroicon-m-x-circle'),

                IconColumn::make('can_edit')
                    ->label('Modifier')
                    ->boolean()
                    ->trueIcon('heroicon-m-pencil')
                    ->falseIcon('heroicon-m-x-circle'),

                IconColumn::make('can_share')
                    ->label('Partager')
                    ->boolean()
                    ->trueIcon('heroicon-m-share')
                    ->falseIcon('heroicon-m-x-circle'),

                TextColumn::make('created_at')
                    ->label('Ajouté le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter une règle')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Nouvelle règle d\'accès')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Enforce: either user_id or role_id must be set, not both
                        if ($data['user_id'] && $data['role_id']) {
                            $data['role_id'] = null;
                        }
                        return $data;
                    }),
            ])

            ->actions([
                EditAction::make()
                    ->modalHeading('Modifier la règle d\'accès'),
                DeleteAction::make()
                    ->label('Supprimer'),
            ])

            ->striped();
    }
}
