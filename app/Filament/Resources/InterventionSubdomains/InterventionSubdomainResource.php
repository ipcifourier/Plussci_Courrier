<?php

namespace App\Filament\Resources\InterventionSubdomains;

use App\Filament\Resources\InterventionSubdomains\Pages\CreateInterventionSubdomain;
use App\Filament\Resources\InterventionSubdomains\Pages\EditInterventionSubdomain;
use App\Filament\Resources\InterventionSubdomains\Pages\ListInterventionSubdomains;
use App\Filament\Resources\InterventionSubdomains\Schemas\InterventionSubdomainForm;
use App\Filament\Resources\InterventionSubdomains\Tables\InterventionSubdomainsTable;
use App\Models\InterventionSubdomain;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InterventionSubdomainResource extends Resource
{
    protected static ?string $model = InterventionSubdomain::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Sous-domaines';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 22;

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
        return InterventionSubdomainForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InterventionSubdomainsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInterventionSubdomains::route('/'),
            'create' => CreateInterventionSubdomain::route('/create'),
            'edit' => EditInterventionSubdomain::route('/{record}/edit'),
        ];
    }
}
