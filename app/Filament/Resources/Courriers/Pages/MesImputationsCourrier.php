<?php

namespace App\Filament\Resources\Courriers\Pages;

use App\Filament\Resources\Courriers\CourrierResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MesImputationsCourrier extends ListRecords
{
    protected static string $resource = CourrierResource::class;

    public function getTitle(): string
    {
        return 'Mes imputations';
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->whereHas('imputations', function (Builder $query): void {
                $query
                    ->where('destinataire_id', Auth::id())
                    ->where('statut_traitement', 'En attente');
            });
    }
}
