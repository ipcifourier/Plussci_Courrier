<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasPermissionTo('admin.users.view')
        );
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ImageEntry::make('avatar_path')
                ->label('Photo')
                ->disk('public')
                ->circular()
                ->visible(fn ($record): bool => filled($record?->avatar_path)),
            TextEntry::make('name')->label('Nom complet'),
            TextEntry::make('email')->label('Email')->copyable(),
            TextEntry::make('departement.nom')->label('Département')->placeholder('—'),
            TextEntry::make('poste')->label('Poste')->placeholder('—'),
            TextEntry::make('is_active')
                ->label('Statut')
                ->formatStateUsing(fn (bool $state): string => $state ? 'Actif' : 'Désactivé')
                ->badge(),
            TextEntry::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i'),
            TextEntry::make('email_verified_at')->label('Email vérifié le')->dateTime('d/m/Y H:i')->placeholder('Non vérifié'),
            TextEntry::make('roles.name')
                ->label('Rôles')
                ->badge()
                ->separator(',')
                ->columnSpanFull(),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view'   => ViewUser::route('/{record}'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }
}
