<?php

namespace App\Filament\Resources\Courriers\Pages;

use App\Filament\Resources\Courriers\CourrierResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MesCourriersATraiter extends ListRecords
{
    protected static string $resource = CourrierResource::class;

    public function getTitle(): string
    {
        return 'Mes courriers a traiter';
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->whereHas('imputations', function (Builder $query): void {
                $query
                    ->where('destinataire_id', Auth::id())
                    ->whereIn('statut_traitement', ['En attente', 'En cours']);
            })
            ->whereIn('statut', ['Nouveau', 'En cours']);
    }
}
