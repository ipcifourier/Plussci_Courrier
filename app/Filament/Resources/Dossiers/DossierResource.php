<?php

namespace App\Filament\Resources\Dossiers;

use App\Filament\Resources\Dossiers\Pages\CreateDossier;
use App\Filament\Resources\Dossiers\Pages\EditDossier;
use App\Filament\Resources\Dossiers\Pages\ListDossiers;
use App\Filament\Resources\Dossiers\Pages\ViewDossier;
use App\Filament\Resources\Dossiers\RelationManagers\DossierChildrenRelationManager;
use App\Filament\Resources\Dossiers\RelationManagers\DossierDocumentsRelationManager;
use App\Filament\Resources\Dossiers\Schemas\DossierForm;
use App\Filament\Resources\Dossiers\Tables\DossiersTable;
use App\Models\Dossier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Facades\Auth;

class DossierResource extends Resource
{


    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User)) {
            return false;
        }
        // Dynamic access: Super Admin, GED, GTT Responsable, or any dossier permission
        return $user->hasRole('Super Admin')
            || $user->hasPermissionTo('ged.dossiers.view')
            || $user->hasRole('GTT Responsable');
    }

    protected static ?string $model = Dossier::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;
    protected static ?string $navigationLabel = 'Dossiers GED';
    protected static ?string $recordTitleAttribute = 'libelle';

    public static function getNavigationGroup(): ?string
    {
        return 'GED';
    }

    public static function form(Schema $schema): Schema
    {
        return DossierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DossiersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->visibleTo(Auth::user());
    }

    public static function getRelations(): array
    {
        return [
            DossierChildrenRelationManager::class,
            DossierDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDossiers::route('/'),
            'create' => CreateDossier::route('/create'),
            'view' => ViewDossier::route('/{record}'),
            'edit' => EditDossier::route('/{record}/edit'),
        ];
    }
}
