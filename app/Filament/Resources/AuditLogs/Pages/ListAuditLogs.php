<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Widgets\AuditActivityChartWidget;
use App\Filament\Widgets\AuditStatsOverview;
use App\Filament\Widgets\AuditTopActorsWidget;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            AuditStatsOverview::class,
            AuditActivityChartWidget::class,
            AuditTopActorsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_audit_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(function (): bool {
                    return $this->canExport(Auth::user());
                })
                ->url(fn (): string => route('audit.logs.export', array_merge(
                    request()->query(),
                    ['format' => 'csv'],
                )))
                ->extraAttributes(['class' => 'pluss-audit-export-action pluss-audit-export-action--csv'])
                ->openUrlInNewTab(),
            Action::make('export_audit_xlsx')
                ->label('Export XLSX')
                ->icon('heroicon-o-document-chart-bar')
                ->color('primary')
                ->visible(function (): bool {
                    return $this->canExport(Auth::user());
                })
                ->url(fn (): string => route('audit.logs.export', array_merge(
                    request()->query(),
                    ['format' => 'xlsx'],
                )))
                ->extraAttributes(['class' => 'pluss-audit-export-action pluss-audit-export-action--xlsx'])
                ->openUrlInNewTab(),
            // AU5 — Export PDF
            Action::make('export_audit_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document')
                ->color('danger')
                ->visible(function (): bool {
                    return $this->canExport(Auth::user());
                })
                ->url(fn (): string => route('audit.logs.export.pdf', request()->query()))
                ->openUrlInNewTab(),
        ];
    }

    protected function canExport(mixed $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $freshUser = User::query()->find($user->id);

        if (! $freshUser instanceof User) {
            return false;
        }

        if ($freshUser->hasRole('Super Admin')) {
            return true;
        }

        try {
            return $freshUser->hasPermissionTo('audit.export');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
