<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Departement;
use App\Models\Gtt;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informations personnelles')
                ->columns(2)
                ->schema([
                    Forms\Components\FileUpload::make('avatar_path')
                        ->label('Photo de profil')
                        ->image()
                        ->disk('public')
                        ->directory('avatars')
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('1:1')
                        ->imageResizeTargetWidth(200)
                        ->imageResizeTargetHeight(200)
                        ->maxSize(2048)
                        ->helperText('Format image, max 2 Mo. La photo sera rognée en carré.')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('name')
                        ->label('Nom complet')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Adresse e-mail')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(table: 'users', column: 'email', ignoreRecord: true),

                    Forms\Components\Select::make('departement_id')
                        ->label('Département')
                        ->options(fn () => Departement::orderBy('nom')->pluck('nom', 'id')->toArray())
                        ->searchable()
                        ->nullable(),

                    Forms\Components\Select::make('gtt_id')
                        ->label('GTT')
                        ->relationship('gtt', 'name', modifyQueryUsing: fn ($query) => $query->visibleTo(Auth::user()))
                        ->searchable()
                        ->nullable(),

                    Forms\Components\TextInput::make('poste')
                        ->label('Poste / Fonction')
                        ->maxLength(255)
                        ->nullable(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Utilisateur actif')
                        ->default(true)
                        ->onColor('success')
                        ->offColor('danger')
                        ->inline(false)
                        ->helperText('Un utilisateur désactivé ne peut plus accéder à l\'interface admin.')
                        ->disabled(fn ($record): bool => $record && Auth::id() === $record->getKey()),
                ]),

            Section::make('Sécurité')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Mot de passe')
                        ->password()
                        ->revealable()
                        ->default('')
                        ->required(fn (string $context): bool => $context === 'create')
                        ->visible(fn (string $context): bool => $context === 'create')
                        ->rule(fn (?string $state, string $context) => ($context === 'create' || filled($state))
                            ? Password::min(8)->letters()->mixedCase()->numbers()->symbols()
                            : null)
                        ->confirmed()
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText(fn (string $context): ?string => $context !== 'create'
                            ? 'Laisser vide pour conserver l\'actuel. Si renseigné: 8+ caractères, maj/min, chiffre, symbole.'
                            : '8+ caractères, maj/min, chiffre, symbole.'),

                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirmer le mot de passe')
                        ->password()
                        ->revealable()
                        ->default('')
                        ->required(fn (string $context): bool => $context === 'create')
                        ->visible(fn (string $context): bool => $context === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                ]),

            Section::make('Rôles & droits')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('Rôles')
                        ->multiple()
                        ->options(fn () => Role::where('guard_name', 'web')->orderBy('name')->pluck('name', 'name')->toArray())
                        ->preload()
                        ->helperText('Les permissions sont héritées des rôles sélectionnés.')
                        ->dehydrated(true), // must be dehydrated for correct role assignment
                ]),
        ]);
    }
}
