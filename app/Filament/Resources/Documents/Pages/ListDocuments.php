<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Export CSV contextuel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(fn (): bool => Gate::allows('ged.documents.download'))
                ->url(fn (): string => route('ged.export', array_merge(
                    ['resource' => 'documents', 'format' => 'csv'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),

            Action::make('export_xlsx')
                ->label('Export XLSX contextuel')
                ->icon('heroicon-o-table-cells')
                ->color('primary')
                ->visible(fn (): bool => Gate::allows('ged.documents.download'))
                ->url(fn (): string => route('ged.export', array_merge(
                    ['resource' => 'documents', 'format' => 'xlsx'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),

            CreateAction::make()
                ->visible(function () {
                    $user = Auth::user();
                    return $user instanceof User
                        && method_exists($user, 'hasRole')
                        && method_exists($user, 'hasPermissionTo')
                        && (
                            $user->hasRole('Super Admin')
                            || $user->hasRole('GTT Responsable')
                            || $user->hasPermissionTo('ged.documents.create')
                            || $user->hasPermissionTo('gtt.documents.manage')
                        );
                }),
        ];
    }

    public function getTabs(): array
    {
        $userId = Auth::id();

        return [
            'tous' => Tab::make('Tous')
                ->badge(Document::count()),

            'mes' => Tab::make('Mes documents')
                ->badge(Document::where('auteur_id', $userId)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('auteur_id', $userId)),

            'brouillon' => Tab::make('Brouillons')
                ->badge(Document::where('etat_cycle_vie', 'Brouillon')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('etat_cycle_vie', 'Brouillon')),

            'valide' => Tab::make('Validés')
                ->badge(Document::where('etat_cycle_vie', 'Valide')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('etat_cycle_vie', 'Valide')),

            'archive' => Tab::make('Archivés')
                ->badge(Document::where('etat_cycle_vie', 'Archive')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('etat_cycle_vie', 'Archive')),

            'sans_dossier' => Tab::make('Sans dossier')
                ->badge(Document::whereNull('dossier_id')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('dossier_id')),
        ];
    }
}

