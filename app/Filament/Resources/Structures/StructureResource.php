<?php

namespace App\Filament\Resources\Structures;

use App\Filament\Resources\Structures\Pages\CreateStructure;
use App\Filament\Resources\Structures\Pages\EditStructure;
use App\Filament\Resources\Structures\Pages\ListStructures;
use App\Filament\Resources\Structures\Schemas\StructureForm;
use App\Filament\Resources\Structures\Tables\StructuresTable;
use App\Models\Structure;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StructureResource extends Resource
{
    protected static ?string $model = Structure::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nom';

    protected static ?string $navigationLabel = 'Structures';

    protected static ?string $modelLabel = 'Structure';

    protected static ?string $pluralModelLabel = 'Structures';

    public static function form(Schema $schema): Schema
    {
        return StructureForm::configure($schema);
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasPermissionTo('admin.roles.manage')
        );
    }

    public static function table(Table $table): Table
    {
        return StructuresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStructures::route('/'),
            'create' => CreateStructure::route('/create'),
            'edit' => EditStructure::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }
}
