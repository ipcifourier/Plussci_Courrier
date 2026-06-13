<?php

namespace App\Filament\Resources\Dossiers\RelationManagers;

use App\Models\Dossier;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class DossierChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'Sous-dossiers';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('code')
                ->label('Code dossier')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('libelle')
                ->label('Libelle')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('annee_activite')
                ->label('Annee d\'activite')
                ->numeric()
                ->default(fn (): ?int => $this->getOwnerRecord()?->annee_activite)
                ->minValue(2020)
                ->maxValue(2100),

            Forms\Components\Select::make('type_dossier')
                ->label('Niveau de classement')
                ->options(Dossier::typeOptions())
                ->default(fn (): string => $this->getOwnerRecord()?->type_dossier === Dossier::TYPE_YEAR ? Dossier::TYPE_CATEGORY : Dossier::TYPE_SUBCATEGORY)
                ->required(),

            Forms\Components\TextInput::make('ordre_affichage')
                ->label('Ordre d\'affichage')
                ->numeric()
                ->default(fn (): int => ((int) Dossier::query()->where('parent_id', $this->getOwnerRecord()->getKey())->max('ordre_affichage')) + 10)
                ->minValue(0),

            Forms\Components\Select::make('owner_id')
                ->label('Responsable')
                ->relationship('owner', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->default(fn () => Auth::id()),

            Forms\Components\Select::make('confidentialite')
                ->options([
                    'Standard'      => 'Standard',
                    'Confidentiel'  => 'Confidentiel',
                    'Personnel'     => 'Personnel',
                ])
                ->default('Standard')
                ->required(),

            Forms\Components\Select::make('statut')
                ->options([
                    'Actif'   => 'Actif',
                    'Clos'    => 'Clos',
                    'Archive' => 'Archivé',
                ])
                ->default('Actif')
                ->required(),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withHierarchyContext())
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('indented_label')
                    ->label('Arborescence')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => \App\Filament\Resources\Dossiers\DossierResource::getUrl('view', ['record' => $record]))
                    ->color('primary'),

                TextColumn::make('breadcrumb_path')
                    ->label('Fil d\'Ariane')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('annee_activite')
                    ->label('Annee')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                BadgeColumn::make('type_dossier')
                    ->label('Niveau')
                    ->formatStateUsing(fn (?string $state): string => Dossier::typeOptions()[$state] ?? 'Dossier standard')
                    ->colors([
                        'success' => Dossier::TYPE_YEAR,
                        'info' => Dossier::TYPE_CATEGORY,
                        'warning' => Dossier::TYPE_SUBCATEGORY,
                        'gray' => Dossier::TYPE_STANDARD,
                    ]),

                TextColumn::make('owner.name')
                    ->label('Responsable')
                    ->searchable(),

                TextColumn::make('documents_count')
                    ->label('Documents')
                    ->counts('documents')
                    ->badge()
                    ->color('gray'),

                BadgeColumn::make('statut')
                    ->label('Statut')
                    ->sortable(),

                BadgeColumn::make('confidentialite')
                    ->label('Confidentialité')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Nouveau sous-dossier'),
            ])
            ->actions([
                Action::make('voir_modal')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record): string => 'Lecture du sous-dossier ' . ($record->code ?? ('#' . $record->id)))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalContent(fn ($record) => view('filament.modals.dossier-preview', [
                        'record' => $record,
                    ])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('code');
    }
}
