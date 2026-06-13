<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\DocumentWorkflowStep;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class WorkflowSlaOverviewWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        return $user->hasRole('Manager')
            || $user->hasRole('Super Admin')
            || $user->can('admin.roles.manage');
    }

    protected function getStats(): array
    {
        $pendingCurrentSteps = DocumentWorkflowStep::query()
            ->where('status', 'pending')
            ->whereHas('workflow', fn ($query) => $query
                ->where('status', 'pending')
                ->whereColumn('current_step_order', 'document_workflow_steps.step_order'))
            ->get(['id', 'created_at', 'due_at', 'sla_hours']);

        $overdue = $pendingCurrentSteps
            ->filter(fn (DocumentWorkflowStep $step): bool => $step->isOverdue())
            ->count();

        $escalatedToday = DocumentWorkflowStep::query()
            ->whereDate('escalated_at', now()->toDateString())
            ->count();

        $pendingEscalated = DocumentWorkflowStep::query()
            ->where('status', 'pending')
            ->whereNotNull('escalated_at')
            ->count();

        return [
            Stat::make('Etapes workflow en retard', (string) $overdue)
                ->description($pendingCurrentSteps->count() . ' etapes actives')
                ->color($overdue > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-clock')
                ->url(DocumentResource::getUrl('workflow-alerts')),

            Stat::make('Escalades aujourd\'hui', (string) $escalatedToday)
                ->description('Declenchees sur les 24h')
                ->color($escalatedToday > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-arrow-trending-up')
                ->url(DocumentResource::getUrl('workflow-alerts')),

            Stat::make('Escalades en attente', (string) $pendingEscalated)
                ->description('Etapes deja escaladees')
                ->color($pendingEscalated > 0 ? 'primary' : 'gray')
                ->icon('heroicon-o-bell-alert')
                ->url(DocumentResource::getUrl('workflow-alerts')),
        ];
    }
}
