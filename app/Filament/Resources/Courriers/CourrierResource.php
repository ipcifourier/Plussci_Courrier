<?php

namespace App\Filament\Resources\Courriers;

use App\Filament\Resources\Courriers\Pages\CreateCourrier;
use App\Filament\Resources\Courriers\Pages\CourriersAApprouver;
use App\Filament\Resources\Courriers\Pages\CourriersASigner;
use App\Filament\Resources\Courriers\Pages\EditCourrier;
use App\Filament\Resources\Courriers\Pages\ListCourriers;
use App\Filament\Resources\Courriers\Pages\MesImputationsCourrier;
use App\Filament\Resources\Courriers\Pages\MesCourriersATraiter;
use App\Filament\Resources\Courriers\Pages\ViewCourrier;
use App\Filament\Resources\Courriers\Schemas\CourrierForm;
use App\Filament\Resources\Courriers\Tables\CourriersTable;
use App\Filament\Resources\Shared\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\Courriers\RelationManagers\DocumentsRelationManager;
use App\Models\Courrier;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Illuminate\Support\Facades\Auth;

class CourrierResource extends Resource
{
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        // Super Admin and permission can see all
        if ($user instanceof \App\Models\User && ($user->hasRole('Super Admin') || $user->hasPermissionTo('courriers.viewAny'))) {
            return $query;
        }

        // GTT Responsable: ne voit que les courriers où son GTT a été assigné
        if ($user instanceof \App\Models\User && $user->hasRole('GTT Responsable')) {
            $gttId = $user->gtt?->id;
            return $query->where(function ($q) use ($gttId) {
                if ($gttId) {
                    // Courriers où l'initiateur appartient à son GTT
                    $q->whereHas('initiateur', function ($sub) use ($gttId) {
                        $sub->where('gtt_id', $gttId);
                    });
                    // Courriers où une imputation a été faite à un membre de son GTT
                    $q->orWhereHas('imputations.destinataire.gtt', function ($sub) use ($gttId) {
                        $sub->where('id', $gttId);
                    });
                }
            });
        }

        // Sinon, permission classique
        return $query;
    }
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User)) {
            return false;
        }
        // Dynamic access: Super Admin, GTT Responsable, or any courrier permission
        return $user->hasRole('Super Admin')
            || $user->hasPermissionTo('courriers.viewAny')
            || $user->hasRole('GTT Responsable')
            || $user->hasPermissionTo('courriers.view');
    }

    protected static ?string $model = Courrier::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $recordTitleAttribute = 'reference';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Courriers';
    }

    // ── Global search ─────────────────────────────────────────────────────────

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'objet', 'resume'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Type'    => $record->type,
            'Statut'  => $record->statut,
            'Date'    => $record->date_reception_envoi?->format('d/m/Y') ?? '—',
        ];
    }

    public static function getGlobalSearchResultUrl(\Illuminate\Database\Eloquent\Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    public static function form(Schema $schema): Schema
    {
        return CourrierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourriersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reference')->label('Référence'),
                TextEntry::make('type')->badge(),
                TextEntry::make('date_reception_envoi')->label('Date')->date('d/m/Y'),
                TextEntry::make('objet')->columnSpanFull(),
                TextEntry::make('resume')->columnSpanFull(),
                TextEntry::make('priorite')->badge(),
                TextEntry::make('statut')->badge(),
                TextEntry::make('approval_status')
                    ->label('Validation')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_required' => 'Non requis',
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        default => $state,
                    })
                    ->badge(),
                TextEntry::make('current_approval_level')->label('Niveau en cours'),
                TextEntry::make('signed_at')
                    ->label('État de signature')
                    ->formatStateUsing(fn ($state): string => $state ? 'Signé' : 'Non signé')
                    ->badge(),
                TextEntry::make('signer.name')->label('Signé par'),
                TextEntry::make('signed_at')->label('Date signature')->dateTime('d/m/Y H:i'),
                TextEntry::make('signature_comment')->label('Commentaire signature')->columnSpanFull(),
                TextEntry::make('niveau_confidentialite')->badge(),
                TextEntry::make('correspondant.nom_structure')->label('Correspondant'),
                TextEntry::make('initiateur.name')->label('Agent initiateur'),
                RepeatableEntry::make('approvals')
                    ->label('Circuit d’approbation')
                    ->schema([
                        TextEntry::make('level')->label('Niveau'),
                        TextEntry::make('approver.name')->label('Approbateur'),
                        TextEntry::make('status')->label('Décision')->badge(),
                        TextEntry::make('decided_at')->label('Date décision')->dateTime('d/m/Y H:i'),
                        TextEntry::make('comment')->label('Commentaire')->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                RepeatableEntry::make('imputations')
                    ->label('Imputations')
                    ->schema([
                        TextEntry::make('expediteur.name')->label('Expéditeur'),
                        TextEntry::make('destinataire.name')->label('Destinataire'),
                        TextEntry::make('statut_traitement')->label('Statut')->badge(),
                        TextEntry::make('date_imputation')->label('Date')->dateTime('d/m/Y H:i'),
                        TextEntry::make('instructions')->label('Instructions')->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
            DocumentsRelationManager::class, // C5
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourriers::route('/'),
            'mes-imputations' => MesImputationsCourrier::route('/mes-imputations'),
            'mes-courriers-a-traiter' => MesCourriersATraiter::route('/mes-courriers-a-traiter'),
            'a-approuver' => CourriersAApprouver::route('/a-approuver'),
            'a-signer' => CourriersASigner::route('/a-signer'),
            'create' => CreateCourrier::route('/create'),
            'edit' => EditCourrier::route('/{record}/edit'),
            'view' => ViewCourrier::route('/{record}'),
        ];
    }
}
