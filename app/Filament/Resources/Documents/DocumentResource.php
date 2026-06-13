<?php

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\DocumentsAValider;
use App\Filament\Resources\Documents\Pages\DocumentsWorkflowAlertes;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Pages\ViewDocument;
use App\Filament\Resources\Documents\Schemas\DocumentForm;
use App\Filament\Resources\Documents\Tables\DocumentsTable;
use App\Models\Document;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DocumentResource extends Resource
{
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User)) {
            return false;
        }
        // Align resource visibility with GED permissions while keeping the legacy GTT override.
        return $user->hasRole('Super Admin')
            || $user->hasRole('GTT Responsable')
            || $user->hasPermissionTo('ged.documents.view')
            || $user->hasPermissionTo('ged.documents.create');
    }

    protected static ?string $model = Document::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?string $navigationLabel = 'Documents GED';
    protected static ?string $recordTitleAttribute = 'titre';

    public static function getNavigationGroup(): ?string
    {
        return 'GED';
    }

    // ── Global search ─────────────────────────────────────────────────────────

    public static function getGloballySearchableAttributes(): array
    {
        return ['titre', 'reference_doc', 'type_document'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Référence'  => $record->reference_doc,
            'Type'       => $record->type_document,
            'État'       => $record->etat_cycle_vie,
            'Dossier'    => $record->dossier?->libelle ?? '—',
        ];
    }

    public static function getGlobalSearchResultUrl(\Illuminate\Database\Eloquent\Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('reference_doc')->label('Reference'),
            TextEntry::make('titre')->label('Titre')->columnSpanFull(),
            TextEntry::make('type_document')->label('Type')->badge(),
            TextEntry::make('interventionDomain.name')->label('Domaine d\'intervention')->placeholder('—'),
            TextEntry::make('interventionSubdomain.name')->label('Sous-domaine')->placeholder('—'),
            TextEntry::make('gtt.name')->label('GTT')->placeholder('—'),
            TextEntry::make('dossier.libelle')->label('Dossier GED')->placeholder('—'),
            TextEntry::make('auteur.name')->label('Auteur')->placeholder('—'),
            TextEntry::make('etat_cycle_vie')->label('Etat')->badge(),
            TextEntry::make('collaboration_enabled')
                ->label('Co-édition')
                ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                ->badge(),
            TextEntry::make('finalized_read_only_at')
                ->label('Finalisé en lecture seule le')
                ->dateTime('d/m/Y H:i')
                ->placeholder('—'),
            TextEntry::make('finalizedReadOnlyBy.name')
                ->label('Finalisé par')
                ->placeholder('—'),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Documents\RelationManagers\DocumentSignaturesRelationManager::class,
            \App\Filament\Resources\Documents\RelationManagers\DocumentVersionsRelationManager::class,
            \App\Filament\Resources\Documents\RelationManagers\DocumentAccessRulesRelationManager::class,
            \App\Filament\Resources\Documents\RelationManagers\DocumentSharesRelationManager::class,
            \App\Filament\Resources\Documents\RelationManagers\DocumentSessionsRelationManager::class,
                \App\Filament\Resources\Documents\RelationManagers\SYSGEDShareJournalRelationManager::class,
            \App\Filament\Resources\Documents\RelationManagers\ArchiveRecordsRelationManager::class,
            \App\Filament\Resources\Shared\RelationManagers\CommentsRelationManager::class,
            \App\Filament\Resources\Documents\RelationManagers\DocumentWorkflowsRelationManager::class,
        ];
    }

    /**
     * Scope the query so that documents with per-doc access rules are only
     * returned to users who match at least one matching can_view rule.
     * Documents with no rules are visible to all users who have the global
     * ged.documents.view permission (handled by the policy).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->visibleTo(Auth::user());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'to-approve' => DocumentsAValider::route('/a-valider'),
            'workflow-alerts' => DocumentsWorkflowAlertes::route('/workflow-alertes'),
            'create' => CreateDocument::route('/create'),
            'view' => ViewDocument::route('/{record}'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }
}
