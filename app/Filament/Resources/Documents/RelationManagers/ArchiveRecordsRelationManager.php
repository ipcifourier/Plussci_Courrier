<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Services\ArchiveService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ArchiveRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'archiveRecord';

    protected static ?string $title = 'Archivage électronique';

    public function isReadOnly(): bool
    {
        return false;
    }

    // ── Form (used by CreateAction to manually archive) ───────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Forms\Components\Textarea::make('reason')
                ->label('Motif d\'archivage')
                ->rows(2)
                ->maxLength(500)
                ->nullable()
                ->placeholder('Ex : Document finalisé, transition vers la conservation à long terme…'),

            Forms\Components\TextInput::make('legal_basis')
                ->label('Base légale / Référence réglementaire')
                ->maxLength(255)
                ->nullable()
                ->placeholder('Ex : Code des marchés publics art. 55, Loi 17-95…'),

            Forms\Components\Select::make('retention_years')
                ->label('Durée de conservation (années)')
                ->options(array_combine(range(1, 30), array_map(fn ($y) => $y . ' an' . ($y > 1 ? 's' : ''), range(1, 30))))
                ->default(5)
                ->required()
                ->native(false),

        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->heading('Enregistrement d\'archive')
            ->description('Conservation légale à long terme du document et vérification d\'intégrité.')
            ->emptyStateHeading('Non archivé')
            ->emptyStateDescription('Ce document n\'a pas encore d\'enregistrement d\'archive officiel.')
            ->emptyStateIcon('heroicon-o-archive-box')

            ->columns([
                TextColumn::make('archived_at')
                    ->label('Archivé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('archivedBy.name')
                    ->label('Par')
                    ->placeholder('—'),

                TextColumn::make('reason')
                    ->label('Motif')
                    ->limit(50)
                    ->placeholder('—'),

                TextColumn::make('legal_basis')
                    ->label('Base légale')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('retention_years')
                    ->label('Rétention')
                    ->suffix(' ans')
                    ->sortable(),

                TextColumn::make('retention_expires_at')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->color(fn ($record): string => $record?->isExpired() ? 'danger' : 'gray')
                    ->sortable(),

                BadgeColumn::make('integrity_status')
                    ->label('Intégrité')
                    ->formatStateUsing(fn ($record): string => $record->integrityLabel())
                    ->color(fn ($record): string => $record->integrityColor()),

                TextColumn::make('verified_at')
                    ->label('Vérifié le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->headerActions([
                CreateAction::make()
                    ->label('Archiver ce document')
                    ->icon('heroicon-m-archive-box-arrow-down')
                    ->color('warning')
                    ->modalHeading('Archivage électronique')
                    ->modalDescription('Enregistrer ce document dans l\'archive électronique officielle. L\'état du document passera à "Archivé".')
                    ->using(function (array $data): \App\Models\ArchiveRecord {
                        $document = $this->getOwnerRecord();
                        $user     = Auth::user();

                        return app(ArchiveService::class)->archiveDocument(
                            document:       $document,
                            user:           $user,
                            reason:         $data['reason'] ?? '',
                            legalBasis:     $data['legal_basis'] ?? '',
                            retentionYears: (int) $data['retention_years'],
                        );
                    })
                    ->after(function (): void {
                        Notification::make()
                            ->title('Document archivé')
                            ->body('L\'enregistrement d\'archive a été créé et l\'empreinte SHA-256 calculée.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => ! $this->getOwnerRecord()->archiveRecord()->exists()),
            ])

            ->actions([
                Action::make('verify')
                    ->label('Vérifier l\'intégrité')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Vérification d\'intégrité')
                    ->modalDescription('Recalculer l\'empreinte SHA-256 des fichiers et la comparer avec la valeur enregistrée au moment de l\'archivage.')
                    ->action(function ($record): void {
                        $user   = Auth::user();
                        $status = app(ArchiveService::class)->verifyIntegrity($record, $user);

                        if ($status === 'verified') {
                            Notification::make()
                                ->title('Intégrité confirmée')
                                ->body('L\'empreinte SHA-256 correspond à la valeur archivée. Aucune altération détectée.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Intégrité compromise !')
                                ->body('L\'empreinte SHA-256 ne correspond pas. Le fichier a peut-être été modifié ou corrompu.')
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])

            ->striped();
    }
}
