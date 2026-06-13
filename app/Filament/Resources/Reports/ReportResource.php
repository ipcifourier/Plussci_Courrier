<?php

namespace App\Filament\Resources\Reports;

use App\Filament\Resources\Reports\Pages\CreateReport;
use App\Filament\Resources\Reports\Pages\EditReport;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Reports\Pages\ViewReport;
use App\Filament\Resources\Reports\RelationManagers\RecommendationsRelationManager;
use App\Filament\Resources\Reports\Schemas\ReportForm;
use App\Filament\Resources\Reports\Tables\ReportsTable;
use App\Models\Report;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Rapports';

    protected static ?string $recordTitleAttribute = 'objet';

    protected static ?int $navigationSort = 9;

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
                'reports.create',
                'reports.update',
                'reports.delete',
                'reports.export',
                'reports.approval.submit',
                'reports.approval.approve',
                'reports.approval.reject',
            ])
        );
    }

    public static function form(Schema $schema): Schema
    {
        return ReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('reference')->label('Reference'),
            TextEntry::make('category.name')->label('Categorie')->badge(),
            TextEntry::make('objet')->label('Objet')->columnSpanFull(),
            TextEntry::make('lieu')->label('Lieu')->placeholder('-'),
            TextEntry::make('date_start')->label('Date debut')->date('d/m/Y')->placeholder('-'),
            TextEntry::make('date_end')->label('Date fin')->date('d/m/Y')->placeholder('-'),
            TextEntry::make('organizer.name')->label('Organisateur')->placeholder('-'),
            TextEntry::make('missionCourrier.reference')->label('Courrier mission')->placeholder('-'),
            TextEntry::make('tdrDocument.reference_doc')->label('Document TDR')->placeholder('-'),
            TextEntry::make('status')->label('Statut')->badge(),
            TextEntry::make('approval_status')
                ->label('Validation')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'not_required' => 'Non requise',
                    'pending' => 'En attente',
                    'approved' => 'Approuve',
                    'rejected' => 'Rejete',
                    default => $state,
                })
                ->badge(),
            TextEntry::make('current_approval_level')->label('Niveau en cours')->placeholder('-'),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RecommendationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'create' => CreateReport::route('/create'),
            'view' => ViewReport::route('/{record}'),
            'edit' => EditReport::route('/{record}/edit'),
        ];
    }
}
