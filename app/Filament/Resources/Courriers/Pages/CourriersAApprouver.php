<?php

namespace App\Filament\Resources\Courriers\Pages;

use App\Filament\Resources\Courriers\CourrierResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CourriersAApprouver extends ListRecords
{
    protected static string $resource = CourrierResource::class;

    public function getTitle(): string
    {
        return 'Courriers a approuver';
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('approval_status', 'pending')
            ->whereNotNull('current_approval_level')
            ->whereHas('approvals', function (Builder $query): void {
                $query
                    ->where('approver_id', Auth::id())
                    ->where('status', 'pending')
                    ->whereColumn('level', 'courriers.current_approval_level');
            });
    }
}
