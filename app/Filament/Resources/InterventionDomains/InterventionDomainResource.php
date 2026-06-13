<?php

namespace App\Filament\Resources\InterventionDomains;

use App\Filament\Resources\InterventionDomains\Pages\CreateInterventionDomain;
use App\Filament\Resources\InterventionDomains\Pages\EditInterventionDomain;
use App\Filament\Resources\InterventionDomains\Pages\ListInterventionDomains;
use App\Filament\Resources\InterventionDomains\RelationManagers\InterventionSubdomainsRelationManager;
use App\Filament\Resources\InterventionDomains\Schemas\InterventionDomainForm;
use App\Filament\Resources\InterventionDomains\Tables\InterventionDomainsTable;
use App\Models\InterventionDomain;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InterventionDomainResource extends Resource
{
    protected static ?string $model = InterventionDomain::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static ?string $navigationLabel = 'Domaines intervention';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 21;

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasPermissionTo('admin.roles.manage')
        );
    }

    public static function form(Schema $schema): Schema
    {
        return InterventionDomainForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InterventionDomainsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InterventionSubdomainsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInterventionDomains::route('/'),
            'create' => CreateInterventionDomain::route('/create'),
            'edit' => EditInterventionDomain::route('/{record}/edit'),
        ];
    }
}
