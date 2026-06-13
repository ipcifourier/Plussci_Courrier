<?php

namespace App\Filament\Pages;

use App\Models\ArchiveRecord;
use App\Models\User;
use App\Services\ArchiveService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ArchivePage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.archive';

    protected static ?string $navigationLabel = 'Registre d\'archive';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 30;

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
                'ged.documents.view',
                'ged.documents.download',
                'ged.dossiers.view',
            ])
        );
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ArchiveRecord::query()
                    ->with(['document', 'archivedBy', 'verifiedBy'])
            )
            ->heading('Registre d\'archivage électronique')
            ->description('Tous les documents archivés avec leur empreinte d\'intégrité et leur durée de conservation légale.')
            ->emptyStateHeading('Aucun document archivé')
            ->emptyStateDescription('Archivez un document depuis sa fiche pour le voir apparaître ici.')
            ->emptyStateIcon('heroicon-o-archive-box')

            ->columns([
                TextColumn::make('document.reference_doc')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('document.titre')
                    ->label('Titre')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record): string => $record->document?->titre ?? ''),

                TextColumn::make('document.type_document')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('archived_at')
                    ->label('Archivé le')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('archivedBy.name')
                    ->label('Par')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('retention_years')
                    ->label('Rétention')
                    ->suffix(' ans')
                    ->sortable(),

                TextColumn::make('retention_expires_at')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record): string => $record->isExpired() ? 'danger' : 'success'),

                BadgeColumn::make('integrity_status')
                    ->label('Intégrité')
                    ->formatStateUsing(fn ($record): string => $record->integrityLabel())
                    ->color(fn ($record): string => $record->integrityColor()),

                TextColumn::make('verified_at')
                    ->label('Dernière vérif.')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Jamais')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('legal_basis')
                    ->label('Base légale')
                    ->placeholder('—')
                    ->limit(35)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('integrity_status')
                    ->label('Statut d\'intégrité')
                    ->options([
                        'pending'   => 'En attente',
                        'verified'  => 'Intègre',
                        'corrupted' => 'Corrompu',
                    ]),

                SelectFilter::make('document.type_document')
                    ->label('Type de document')
                    ->relationship('document', 'type_document')
                    ->searchable(),
            ])

            ->actions([
                Action::make('verify_integrity')
                    ->label('Vérifier')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Vérification d\'intégrité SHA-256')
                    ->modalDescription('Recalculer l\'empreinte des fichiers et la comparer avec la valeur enregistrée.')
                    ->action(function (ArchiveRecord $record): void {
                        $user   = Auth::user();
                        $status = app(ArchiveService::class)->verifyIntegrity($record, $user);

                        if ($status === 'verified') {
                            Notification::make()
                                ->title('Intégrité confirmée ✓')
                                ->body("Document #{$record->document?->reference_doc} — empreinte SHA-256 valide.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Intégrité compromise !')
                                ->body("Document #{$record->document?->reference_doc} — empreinte SHA-256 ne correspond pas. Vérifiez manuellement.")
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('view_document')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (ArchiveRecord $record): string =>
                        \App\Filament\Resources\Documents\DocumentResource::getUrl('view', ['record' => $record->document_id])
                    ),
            ])

            ->headerActions([
                Action::make('export_manifest')
                    ->label('Exporter le registre (JSON)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (): void {
                        $records  = ArchiveRecord::with(['document', 'archivedBy', 'verifiedBy'])->get();
                        $manifest = app(ArchiveService::class)->generateManifestJson($records);

                        $filename = 'registre-archive-' . now()->format('Ymd-His') . '.json';
                        $json     = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        // Stream as download via redirect to a data URI workaround
                        // (standard approach in Filament: store then redirect)
                        $path = storage_path('app/public/' . $filename);
                        file_put_contents($path, $json);

                        Notification::make()
                            ->title('Registre exporté')
                            ->body("Fichier : {$filename}")
                            ->success()
                            ->send();
                    }),

                Action::make('verify_all')
                    ->label('Vérifier tout')
                    ->icon('heroicon-o-shield-check')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Vérification globale d\'intégrité')
                    ->modalDescription('Recalculer l\'empreinte SHA-256 de tous les documents archivés. Cette opération peut prendre plusieurs minutes.')
                    ->action(function (): void {
                        $user    = Auth::user();
                        $service = app(ArchiveService::class);
                        $records = ArchiveRecord::with('document.media')->get();

                        $ok        = 0;
                        $corrupted = 0;

                        foreach ($records as $rec) {
                            $status = $service->verifyIntegrity($rec, $user);
                            if ($status === 'verified') {
                                $ok++;
                            } else {
                                $corrupted++;
                            }
                        }

                        Notification::make()
                            ->title("Vérification terminée : {$ok} intègre(s), {$corrupted} corrompu(s)")
                            ->color($corrupted > 0 ? 'danger' : 'success')
                            ->persistent($corrupted > 0)
                            ->send();
                    }),
            ])

            ->defaultSort('archived_at', 'desc')
            ->striped();
    }

    // ── Page header ───────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [];
    }
}
