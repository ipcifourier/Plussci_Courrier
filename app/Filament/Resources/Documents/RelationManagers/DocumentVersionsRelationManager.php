<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Services\DocumentVersioningService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DocumentVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Historique des versions';

    // ── Form (unused — we manage creation via a custom Action) ───────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_version')
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['document', 'media', 'creator']))
            ->columns([
                TextColumn::make('numero_version')
                    ->label('Version')
                    ->badge()
                    ->color(fn ($record): string => $record->document?->version_courante_id === $record->id
                        ? 'success'
                        : 'gray')
                    ->formatStateUsing(fn ($state, $record): string =>
                        $state . ($record->document?->version_courante_id === $record->id ? ' ✓' : '')
                    ),

                TextColumn::make('media.file_name')
                    ->label('Fichier')
                    ->limit(45)
                    ->tooltip(fn ($record): ?string => $record->media?->file_name)
                    ->placeholder('—'),

                TextColumn::make('media.human_readable_size')
                    ->label('Taille')
                    ->placeholder('—'),

                BadgeColumn::make('ocr_status')
                    ->label('OCR')
                    ->formatStateUsing(fn ($record) => $record->ocrStatusLabel())
                    ->color(fn ($state): string => match ($state) {
                        'completed'   => 'success',
                        'processing'  => 'warning',
                        'failed'      => 'danger',
                        default       => 'gray',
                    }),

                TextColumn::make('commentaire_version')
                    ->label('Note')
                    ->limit(50)
                    ->placeholder('—'),

                TextColumn::make('creator.name')
                    ->label('Par')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('nouvelle_version')
                    ->label('Nouvelle version')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->modalHeading('Ajouter une nouvelle version')
                    ->modalDescription('Téléversez un fichier révisé. Un doublon (même contenu) sera automatiquement détecté.')
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\FileUpload::make('fichier')
                            ->label('Fichier révisé')
                            ->required()
                            ->disk('local')
                            ->directory('tmp/doc-versions')
                            ->preserveFilenames()
                            ->maxSize(20480)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                'image/jpeg',
                                'image/png',
                                'text/plain',
                                'application/zip',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\Select::make('version_type')
                            ->label('Type de version')
                            ->options([
                                'minor' => 'Mineure — correction/ajout mineur  (ex : 1.0 → 1.1)',
                                'major' => 'Majeure — refonte significative      (ex : 1.0 → 2.0)',
                            ])
                            ->default('minor')
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('commentaire')
                            ->label('Note de version')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Décrivez les modifications apportées…')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data): void {
                        $document     = $this->getOwnerRecord();
                        $relativePath = $data['fichier'];
                        $absolutePath = Storage::disk('local')->path($relativePath);
                        $originalName = basename($relativePath);
                        $mime         = mime_content_type($absolutePath) ?: 'application/octet-stream';

                        try {
                            $version = app(DocumentVersioningService::class)->createVersion(
                                document:     $document,
                                tempPath:     $absolutePath,
                                originalName: $originalName,
                                mimeType:     $mime,
                                comment:      $data['commentaire'] ?? '',
                                type:         $data['version_type'],
                            );

                            Notification::make()
                                ->title("Version {$version->numero_version} créée")
                                ->body('Le fichier a été indexé et le pipeline OCR est lancé.')
                                ->success()
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('Doublon détecté')
                                ->body($e->getMessage())
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('download')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record): ?string => $record->media?->getUrl())
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => $record->media !== null),

                Action::make('set_current')
                    ->label('Définir comme courante')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Changer la version courante')
                    ->modalDescription(fn ($record): string =>
                        "Utiliser la version {$record->numero_version} comme version courante de ce document ?"
                    )
                    ->action(function ($record): void {
                        app(DocumentVersioningService::class)
                            ->setCurrentVersion($this->getOwnerRecord(), $record);

                        Notification::make()
                            ->title("Version {$record->numero_version} définie comme version courante")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool =>
                        $this->getOwnerRecord()->version_courante_id !== $record->id
                    ),
            ])
            ->emptyStateHeading('Aucune version enregistrée')
            ->emptyStateDescription('Téléversez un fichier via le bouton "Nouvelle version" pour commencer le suivi.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
