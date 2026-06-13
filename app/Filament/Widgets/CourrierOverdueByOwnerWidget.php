<?php

namespace App\Filament\Widgets;

use App\Models\Courrier;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * C4 — Top responsables avec courriers en retard.
 */
class CourrierOverdueByOwnerWidget extends BaseWidget
{
    protected static ?string $heading = 'Courriers en retard par responsable';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return \App\Models\User::query()
                    ->withCount(['courriers as overdue_count' => function ($q): void {
                        $q->whereNotIn('statut', ['Traité', 'Archivé'])
                          ->whereNotNull('delai_reponse')
                          ->whereDate('delai_reponse', '<', Carbon::today());
                    }])
                    ->having('overdue_count', '>', 0)
                    ->orderByDesc('overdue_count')
                    ->limit(10);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Responsable')
                    ->searchable(),

                TextColumn::make('overdue_count')
                    ->label('Courriers en retard')
                    ->badge()
                    ->color(fn (int $state): string => $state > 5 ? 'danger' : 'warning')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
