<?php

namespace App\Filament\Resources\ReportCategories;

use App\Filament\Resources\ReportCategories\Pages\CreateReportCategory;
use App\Filament\Resources\ReportCategories\Pages\EditReportCategory;
use App\Filament\Resources\ReportCategories\Pages\ListReportCategories;
use App\Filament\Resources\ReportCategories\Schemas\ReportCategoryForm;
use App\Filament\Resources\ReportCategories\Tables\ReportCategoriesTable;
use App\Models\ReportCategory;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ReportCategoryResource extends Resource
{
    protected static ?string $model = ReportCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Categories rapports';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 24;

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasPermissionTo('reports.templates.manage')
        );
    }

    public static function form(Schema $schema): Schema
    {
        return ReportCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportCategories::route('/'),
            'create' => CreateReportCategory::route('/create'),
            'edit' => EditReportCategory::route('/{record}/edit'),
        ];
    }
}
