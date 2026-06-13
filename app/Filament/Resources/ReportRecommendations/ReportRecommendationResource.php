<?php

namespace App\Filament\Resources\ReportRecommendations;

use App\Filament\Resources\ReportRecommendations\Pages\CreateReportRecommendation;
use App\Filament\Resources\ReportRecommendations\Pages\EditReportRecommendation;
use App\Filament\Resources\ReportRecommendations\Pages\ListReportRecommendations;
use App\Filament\Resources\ReportRecommendations\Schemas\ReportRecommendationForm;
use App\Filament\Resources\ReportRecommendations\Tables\ReportRecommendationsTable;
use App\Models\ReportRecommendation;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ReportRecommendationResource extends Resource
{
    protected static ?string $model = ReportRecommendation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Suivi recommandations';

    protected static ?string $recordTitleAttribute = 'recommendation';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return 'GED';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasAnyPermission([
                'reports.viewAny',
                'reports.view',
                'reports.recommendations.viewAny',
                'reports.recommendations.create',
                'reports.recommendations.update',
                'reports.recommendations.delete',
            ])
        );
    }

    public static function form(Schema $schema): Schema
    {
        return ReportRecommendationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportRecommendationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportRecommendations::route('/'),
            'create' => CreateReportRecommendation::route('/create'),
            'edit' => EditReportRecommendation::route('/{record}/edit'),
        ];
    }
}
