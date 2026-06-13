<?php

namespace App\Filament\Resources\Dossiers\RelationManagers;

use App\Models\DocumentType;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DossierDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\SpatieMediaLibraryFileUpload::make('fichiers')
                ->label('Fichiers')
                ->collection('documents')
                ->multiple()
                ->reorderable()
                ->appendFiles()
                ->acceptedFileTypes([
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'image/jpeg',
                    'image/png',
                    'text/plain',
                    'application/zip',
                ])
                ->maxSize(20480)
                ->downloadable()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('reference_doc')
                ->label('Référence document')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('titre')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('type_document')
                ->label('Type de document')
                ->required()
                ->options(fn () => DocumentType::query()->orderBy('name')->pluck('name', 'name'))
                ->searchable()
                ->native(false)
                ->createOptionForm([
                    Forms\Components\TextInput::make('name')
                        ->label('Nouveau type de document')
                        ->required()
                        ->maxLength(255)
                        ->unique(table: 'document_types', column: 'name'),
                ])
                ->createOptionUsing(fn (array $data): string => DocumentType::query()->create([
                    'name' => trim($data['name']),
                ])->name),

            Forms\Components\Select::make('courrier_id')
                ->relationship('courrier', 'reference')
                ->label('Courrier lié')
                ->searchable()
                ->preload()
                ->nullable(),

            Forms\Components\Select::make('auteur_id')
                ->relationship('auteur', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->default(fn () => Auth::id()),

            Forms\Components\Select::make('etat_cycle_vie')
                ->label('État cycle de vie')
                ->options([
                    'Brouillon' => 'Brouillon',
                    'Valide'    => 'Validé',
                    'Archive'   => 'Archivé',
                ])
                ->default('Brouillon')
                ->required(),

            Forms\Components\Select::make('confidentiality_level')
                ->label('Confidentialité')
                ->options([
                    'Standard'      => 'Standard',
                    'Confidentiel'  => 'Confidentiel',
                    'Personnel'     => 'Personnel',
                ])
                ->default('Standard')
                ->required(),

            Forms\Components\TagsInput::make('tags_json')
                ->label('Tags')
                ->separator(',')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_doc')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => \App\Filament\Resources\Documents\DocumentResource::getUrl('view', ['record' => $record]))
                    ->color('primary'),

                TextColumn::make('titre')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                BadgeColumn::make('type_document')
                    ->label('Type')
                    ->sortable(),

                TextColumn::make('courrier.reference')
                    ->label('Courrier lié')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('auteur.name')
                    ->label('Auteur')
                    ->searchable(),

                BadgeColumn::make('etat_cycle_vie')
                    ->label('Cycle de vie')
                    ->sortable(),

                BadgeColumn::make('confidentiality_level')
                    ->label('Confidentialité')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('media_count')
                    ->label('Fichiers')
                    ->counts('media')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->icon('heroicon-m-paper-clip'),
            ])
            ->headerActions([
                CreateAction::make()->label('Nouveau document'),
            ])
            ->actions([
                Action::make('download')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->hasMedia('documents'))
                    ->url(fn ($record) => \App\Filament\Resources\Documents\DocumentResource::getUrl('view', ['record' => $record])),

                Action::make('voir_modal')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record): string => 'Lecture du document ' . ($record->reference_doc ?? ('#' . $record->id)))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalContent(fn ($record) => view('filament.modals.media-preview', [
                        'mediaItems' => $record->getMedia('documents'),
                        'emptyMessage' => 'Aucun fichier a afficher pour ce document.',
                    ])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
