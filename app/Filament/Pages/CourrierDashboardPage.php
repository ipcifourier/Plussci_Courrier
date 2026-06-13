<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CourrierKpiStatsWidget;
use App\Filament\Widgets\CourrierOverdueByOwnerWidget;
use App\Filament\Widgets\CourrierTrendChartWidget;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * C1 — Tableau de bord Courriers avec KPIs et tendances.
 */
class CourrierDashboardPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $slug = 'courriers-dashboard';

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static ?string $title = 'Tableau de bord — Courriers';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.courrier-dashboard';

    public static function getNavigationGroup(): ?string
    {
        return 'Courriers';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasAnyPermission(['courriers.view', 'courriers.viewAny', 'courriers.manage'])
        );
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CourrierKpiStatsWidget::class,
            CourrierTrendChartWidget::class,
            CourrierOverdueByOwnerWidget::class,
        ];
    }
}
