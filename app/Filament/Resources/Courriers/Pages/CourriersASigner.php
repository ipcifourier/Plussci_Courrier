<?php

namespace App\Filament\Resources\Courriers\Pages;

use App\Filament\Resources\Courriers\CourrierResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class CourriersASigner extends ListRecords
{
    protected static string $resource = CourrierResource::class;

    public function getTitle(): string
    {
        return 'Courriers a signer';
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('type', 'Sortant')
            ->whereNull('signed_at')
            ->where('statut', 'Traité')
            ->where(function (Builder $query): void {
                $query
                    ->where('requires_approval', false)
                    ->orWhere('approval_status', 'approved');
            });
    }
}
