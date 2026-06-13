<?php

namespace App\Filament\Resources\Gtts;

use App\Filament\Resources\Gtts\Pages\CreateGtt;
use App\Filament\Resources\Gtts\Pages\EditGtt;
use App\Filament\Resources\Gtts\Pages\ListGtts;
use App\Filament\Resources\Gtts\Schemas\GttForm;
use App\Filament\Resources\Gtts\Tables\GttsTable;
use App\Models\Gtt;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GttResource extends Resource
{
    protected static ?string $model = Gtt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'GTT';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!($user instanceof User)) {
            return false;
        }
        // Dynamic access: Super Admin, admin.roles.manage, GTT Responsable, or any GTT permission
        return $user->hasRole('Super Admin')
            || $user->hasPermissionTo('admin.roles.manage')
            || $user->hasRole('GTT Responsable')
            || $user->hasPermissionTo('gtt.documents.view')
            || $user->hasPermissionTo('gtt.members.view');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->visibleTo(Auth::user())
            ->with('responsableUser');
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->hasRole('Super Admin') || $user->hasPermissionTo('admin.roles.manage'));
    }

    public static function form(Schema $schema): Schema
    {
        return GttForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GttsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Gtts\RelationManagers\BureauMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtts::route('/'),
            'create' => CreateGtt::route('/create'),
            'edit' => EditGtt::route('/{record}/edit'),
        ];
    }
}
