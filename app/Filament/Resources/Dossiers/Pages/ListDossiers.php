<?php

namespace App\Filament\Resources\Dossiers\Pages;

use App\Filament\Resources\Dossiers\DossierResource;
use App\Filament\Resources\Dossiers\Widgets\DossiersTreeWidget;
use App\Models\Dossier;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ListDossiers extends ListRecords
{
    protected static string $resource = DossierResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            DossiersTreeWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Export CSV contextuel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(fn (): bool => Gate::allows('ged.dossiers.view'))
                ->url(fn (): string => route('ged.export', array_merge(
                    ['resource' => 'dossiers', 'format' => 'csv'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),

            Action::make('export_xlsx')
                ->label('Export XLSX contextuel')
                ->icon('heroicon-o-table-cells')
                ->color('primary')
                ->visible(fn (): bool => Gate::allows('ged.dossiers.view'))
                ->url(fn (): string => route('ged.export', array_merge(
                    ['resource' => 'dossiers', 'format' => 'xlsx'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),

            Action::make('generer_classement_annuel')
                ->label('Generer une arborescence annuelle')
                ->icon('heroicon-o-folder-plus')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('annee_activite')
                        ->label('Annee d\'activite')
                        ->numeric()
                        ->default((int) date('Y'))
                        ->required()
                        ->minValue(2020)
                        ->maxValue(2100),
                    Forms\Components\Select::make('owner_id')
                        ->label('Responsable des dossiers generes')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->default(fn (): ?int => Auth::id())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $summary = Dossier::generateAnnualTree((int) $data['annee_activite'], (int) $data['owner_id']);

                    Notification::make()
                        ->title('Arborescence annuelle generee')
                        ->body(sprintf(
                            '%d dossiers crees, %d deja presents pour l\'annee %d.',
                            $summary['created'],
                            $summary['existing'],
                            $data['annee_activite'],
                        ))
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $visibleDossiers = Dossier::query()->visibleTo(Auth::user());

        $tabs = [
            'tous' => Tab::make('Tous')
                ->badge((clone $visibleDossiers)->count()),

            'racines_annuelles' => Tab::make('Racines annuelles')
                ->badge((clone $visibleDossiers)->where('type_dossier', Dossier::TYPE_YEAR)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type_dossier', Dossier::TYPE_YEAR)),

            'sans_annee' => Tab::make('Sans annee')
                ->badge((clone $visibleDossiers)->whereNull('annee_activite')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('annee_activite')),
        ];

        foreach ((clone $visibleDossiers)
            ->whereNotNull('annee_activite')
            ->distinct()
            ->orderByDesc('annee_activite')
            ->pluck('annee_activite', 'annee_activite') as $year => $label) {
            $tabs['annee_' . $year] = Tab::make((string) $label)
                ->badge((clone $visibleDossiers)->where('annee_activite', $year)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('annee_activite', $year));
        }

        return $tabs;
    }
}
