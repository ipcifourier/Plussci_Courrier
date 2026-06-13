<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\DocumentWorkflowStep;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MesValidationsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getStats(): array
    {
        $userId = Auth::id();

        if (! $userId) {
            return [
                Stat::make('Mes validations', '0')
                    ->description('Utilisateur non connecte')
                    ->color('gray')
                    ->icon('heroicon-o-shield-check'),
            ];
        }

        $pendingQuery = DocumentWorkflowStep::query()
            ->where('approver_id', $userId)
            ->where('status', 'pending')
            ->whereHas('workflow', fn ($query) => $query
                ->where('status', 'pending')
                ->whereColumn('current_step_order', 'document_workflow_steps.step_order'));

        $steps = (clone $pendingQuery)->get(['id', 'created_at', 'due_at', 'sla_hours']);

        $pending = $steps->count();

        $overdue = $steps->filter(function (DocumentWorkflowStep $step): bool {
            $deadline = $step->deadlineAt();

            return $deadline?->isPast() ?? false;
        })->count();

        $today = $steps->filter(fn (DocumentWorkflowStep $step): bool => $step->created_at?->isToday() ?? false)->count();

        return [
            Stat::make('Mes validations en attente', (string) $pending)
                ->description("{$overdue} en retard · {$today} recues aujourd'hui")
                ->color($overdue > 0 ? 'danger' : ($pending > 0 ? 'warning' : 'success'))
                ->icon('heroicon-o-shield-check')
                ->url(DocumentResource::getUrl('to-approve')),
        ];
    }
}
