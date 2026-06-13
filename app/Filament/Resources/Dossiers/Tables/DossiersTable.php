<?php

namespace App\Filament\Resources\Dossiers\Tables;

use App\Models\Dossier;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DossiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withHierarchyContext())
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('indented_label')
                    ->label('Arborescence')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('libelle', 'like', "%{$search}%");
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderBy('annee_activite', $direction)
                            ->orderBy('parent_id')
                            ->orderBy('ordre_affichage', $direction)
                            ->orderBy('libelle', $direction);
                    }),
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
                TextColumn::make('parent.libelle')->label('Parent')->placeholder('-'),
                TextColumn::make('documents_count')
                    ->label('Documents')
                    ->counts('documents')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('owner.name')->label('Responsable')->searchable(),
                BadgeColumn::make('confidentialite')->sortable(),
                BadgeColumn::make('statut')->sortable(),
                TextColumn::make('ordre_affichage')
                    ->label('Ordre')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('dossier_cible')
                    ->label('Dossier cible')
                    ->form([
                        Forms\Components\TextInput::make('id')
                            ->label('ID dossier')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['id'] ?? null,
                            fn (Builder $innerQuery, $id): Builder => $innerQuery->whereKey((int) $id),
                        );
                    }),
                SelectFilter::make('annee_activite')
                    ->label('Annee d\'activite')
                    ->options(Dossier::yearOptions()),
                SelectFilter::make('type_dossier')
                    ->label('Niveau de classement')
                    ->options(Dossier::typeOptions()),
            ])
            ->actions([
                Action::make('voir_modal')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record): string => 'Lecture du dossier ' . ($record->code ?? ('#' . $record->id)))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalContent(fn ($record) => view('filament.modals.dossier-preview', [
                        'record' => $record,
                    ])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('annee_activite', 'desc');
    }
}
