<?php

namespace App\Filament\Resources\Dossiers\Pages;

use App\Filament\Resources\Dossiers\DossierResource;
use App\Filament\Pages\AcquisitionPage;
use App\Models\Dossier;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDossier extends ViewRecord
{
    protected static string $resource = DossierResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Dossier $record */
        $record = $this->getRecord();
        $root = $record->ancestorChain()->first();

        return [
            Action::make('retour_liste_filtree')
                ->label('Filtrer dans la liste GED')
                ->icon('heroicon-o-funnel')
                ->color('info')
                ->url($this->listUrl(array_filter([
                    'tableFilters[annee_activite][value]' => $record->annee_activite ? (string) $record->annee_activite : null,
                    'tableFilters[dossier_cible][id]' => (string) $record->id,
                ]))),

            Action::make('voir_parent')
                ->label('Voir le parent')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('gray')
                ->visible(fn (): bool => (bool) $record->parent)
                ->url(fn (): string => DossierResource::getUrl('view', ['record' => $record->parent])),

            Action::make('voir_racine')
                ->label('Voir la racine annuelle')
                ->icon('heroicon-o-folder-open')
                ->color('success')
                ->visible(fn (): bool => $root instanceof Dossier)
                ->url(fn (): string => DossierResource::getUrl('view', ['record' => $root])),

            Action::make('acquerir_dans_ce_dossier')
                ->label('Acquisition & OCR ici')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (): string => AcquisitionPage::getUrl([
                    'dossier_id' => (string) $record->id,
                ])),

            Action::make('rattacher_document_existant')
                ->label('Rattacher un document')
                ->icon('heroicon-o-paper-clip')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('document_id')
                        ->label('Document a rattacher')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => Document::query()
                            ->where(function ($query) use ($search): void {
                                $query->where('titre', 'like', "%{$search}%")
                                    ->orWhere('reference_doc', 'like', "%{$search}%");
                            })
                            ->orderByDesc('updated_at')
                            ->limit(30)
                            ->get()
                            ->mapWithKeys(fn (Document $document): array => [
                                $document->id => trim(($document->reference_doc ? $document->reference_doc . ' - ' : '') . $document->titre),
                            ])
                            ->all())
                        ->getOptionLabelUsing(fn ($value): ?string => filled($value)
                            ? Document::query()->whereKey($value)->get()->map(fn (Document $document): string => trim(($document->reference_doc ? $document->reference_doc . ' - ' : '') . $document->titre))->first()
                            : null)
                        ->required(),
                ])
                ->action(function (array $data) use ($record): void {
                    $document = Document::query()->findOrFail($data['document_id']);
                    $previousDossierId = $document->dossier_id;

                    $document->update([
                        'dossier_id' => $record->id,
                    ]);

                    Notification::make()
                        ->title('Document rattache au dossier')
                        ->body($previousDossierId
                            ? 'Le document a ete deplace vers ce dossier GED.'
                            : 'Le document a ete rattache a ce dossier GED.')
                        ->success()
                        ->send();
                }),

            EditAction::make(),
        ];
    }

    protected function getInfolistSchema(): array
    {
        return [
            \Filament\Infolists\Components\TextEntry::make('indented_label')
                ->label('Position dans l\'arborescence'),

            \Filament\Infolists\Components\TextEntry::make('breadcrumb_path')
                ->label('Fil d\'Ariane'),

            \Filament\Infolists\Components\TextEntry::make('type_label')
                ->label('Niveau de classement'),

            \Filament\Infolists\Components\TextEntry::make('annee_activite')
                ->label('Annee d\'activite')
                ->url(fn ($record): ?string => $record->annee_activite ? $this->listUrl([
                    'tableFilters[annee_activite][value]' => (string) $record->annee_activite,
                ]) : null)
                ->placeholder('-'),

            \Filament\Infolists\Components\TextEntry::make('parent.libelle')
                ->label('Dossier parent')
                ->url(fn ($record): ?string => $record->parent ? DossierResource::getUrl('view', ['record' => $record->parent]) : null)
                ->placeholder('-'),

            \Filament\Infolists\Components\TextEntry::make('owner.name')
                ->label('Responsable')
                ->placeholder('-'),

            \Filament\Infolists\Components\TextEntry::make('documents_count')
                ->label('Documents rattaches')
                ->formatStateUsing(fn ($state, $record): string => (string) $record->documents()->count()),

            \Filament\Infolists\Components\TextEntry::make('documents_count_cumules')
                ->label('Documents cumules')
                ->formatStateUsing(fn ($state, $record): string => (string) $record->aggregatedDocumentsCount()),

            \Filament\Infolists\Components\TextEntry::make('children_count')
                ->label('Sous-dossiers')
                ->formatStateUsing(fn ($state, $record): string => (string) $record->children()->count()),

            \Filament\Infolists\Components\TextEntry::make('children_count_cumules')
                ->label('Sous-dossiers cumules')
                ->formatStateUsing(fn ($state, $record): string => (string) $record->aggregatedChildrenCount()),

            \Filament\Infolists\Components\TextEntry::make('description')
                ->label('Description')
                ->placeholder('-'),
        ];
    }

    /**
     * @param array<string, string> $query
     */
    protected function listUrl(array $query = []): string
    {
        $base = DossierResource::getUrl('index');

        if (empty($query)) {
            return $base;
        }

        return $base . '?' . http_build_query($query);
    }
}
