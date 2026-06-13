<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AuditStatsOverview;
use App\Filament\Widgets\AdminWelcomeWidget;
use App\Filament\Widgets\CollaborationStatsOverview;
use App\Filament\Widgets\CourriersStatsOverview;
use App\Filament\Widgets\EcheancesWidget;
use App\Filament\Widgets\AgendaStatsOverview;
use App\Filament\Widgets\GedStatsOverview;
use App\Filament\Widgets\MesImputationsEnAttenteWidget;
use App\Filament\Widgets\MesTachesWidget;
use App\Filament\Widgets\MesValidationsWidget;
use App\Filament\Widgets\TachesEnRetardWidget;
use App\Filament\Widgets\WorkflowSlaOverviewWidget;
use App\Filament\Widgets\UserPermissionsWidget;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminDashboard extends BaseDashboard
{
    private const CACHE_KEY_PREFIX = 'dashboard_widgets_user_';

    /** @var array<class-string, string> */
    private const HEADER_WIDGETS = [
        CourriersStatsOverview::class => 'Courriers',
        GedStatsOverview::class => 'GED',
        CollaborationStatsOverview::class => 'Collaboration',
        AgendaStatsOverview::class => 'Agenda',
        EcheancesWidget::class => 'Echeances',
        MesImputationsEnAttenteWidget::class => 'Mes imputations en attente',
        MesTachesWidget::class => 'Mes taches',
        MesValidationsWidget::class => 'Mes validations en attente',
        TachesEnRetardWidget::class => 'Taches en retard',
        WorkflowSlaOverviewWidget::class => 'Workflow SLA (retards/escalades)',
    ];

    /** @var array<class-string, string> */
    private const FOOTER_WIDGETS = [
        AuditStatsOverview::class      => 'Audit et tracabilite',
        UserPermissionsWidget::class   => 'Mes rôles et permissions',
        AccountWidget::class           => 'Compte utilisateur',
    ];

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public function getTitle(): string
    {
        return $this->isWidgetsMode()
            ? 'Tableau de bord admin'
            : 'Accueil administration';
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl(['widgets' => 1]);
    }

    /**
     * We render widgets via header/footer methods below.
     * Returning an empty list here avoids a duplicate body grid
     * (which was rendering the welcome card a second time).
     */
    public function getWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('showWidgets')
                ->label('Tableau de bord')
                ->icon('heroicon-o-squares-2x2')
                ->visible(fn (): bool => ! $this->isWidgetsMode())
                ->url(static::getUrl(['widgets' => 1])),
            Action::make('showWelcome')
                ->label('Accueil')
                ->icon('heroicon-o-home')
                ->visible(fn (): bool => $this->isWidgetsMode())
                ->url(static::getUrl()),
            Action::make('configureWidgets')
                ->label('Configurer mes widgets')
                ->icon('heroicon-o-adjustments-horizontal')
                ->modalHeading('Personnaliser l\'affichage du tableau de bord')
                ->modalDescription('Choisissez les widgets a afficher quand vous ouvrez le mode Tableau de bord.')
                ->fillForm(fn (): array => [
                    'widgets' => $this->getEnabledWidgets(),
                ])
                ->form([
                    CheckboxList::make('widgets')
                        ->label('Widgets disponibles')
                        ->options($this->getWidgetOptions())
                        ->columns(2),
                ])
                ->action(function (array $data): void {
                    $this->storeEnabledWidgets($data['widgets'] ?? []);

                    Notification::make()
                        ->title('Configuration enregistree')
                        ->body('Vos widgets personnalises sont maintenant appliques.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if (! $this->isWidgetsMode()) {
            return $this->filterAuthorizedWidgets([
                AdminWelcomeWidget::class,
            ]);
        }

        return $this->filterEnabledWidgets(array_keys(self::HEADER_WIDGETS));
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        if (! $this->isWidgetsMode()) {
            return 1;
        }

        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    protected function getFooterWidgets(): array
    {
        if (! $this->isWidgetsMode()) {
            return [];
        }

        return $this->filterEnabledWidgets(array_keys(self::FOOTER_WIDGETS));
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 2,
        ];
    }

    /** @return array<string, string> */
    private function getWidgetOptions(): array
    {
        return array_intersect_key(
            self::HEADER_WIDGETS + self::FOOTER_WIDGETS,
            array_flip($this->getAllWidgets()),
        );
    }

    /** @return list<class-string> */
    private function filterEnabledWidgets(array $widgets): array
    {
        $widgets = $this->filterAuthorizedWidgets($widgets);
        $enabled = $this->getEnabledWidgets();

        return array_values(array_filter(
            $widgets,
            fn (string $widget): bool => in_array($widget, $enabled, true),
        ));
    }

    /** @param list<class-string> $widgets
     *  @return list<class-string>
     */
    private function filterAuthorizedWidgets(array $widgets): array
    {
        return array_values(array_filter(
            $widgets,
            fn (string $widget): bool => $this->canViewWidget($widget),
        ));
    }

    /** @return list<class-string> */
    private function getEnabledWidgets(): array
    {
        $userId = Auth::id();
        $allWidgets = $this->getAllWidgets();

        if (! $userId) {
            return $allWidgets;
        }

        $cacheKey = self::CACHE_KEY_PREFIX . $userId;

        if (! Cache::has($cacheKey)) {
            Cache::forever($cacheKey, $allWidgets);

            return $allWidgets;
        }

        $selected = Cache::get($cacheKey, []);

        if (! is_array($selected)) {
            return $allWidgets;
        }

        return array_values(array_intersect($allWidgets, $selected));
    }

    /** @param list<class-string> $widgets */
    private function storeEnabledWidgets(array $widgets): void
    {
        $userId = Auth::id();

        if (! $userId) {
            return;
        }

        $validWidgets = array_values(array_intersect($this->getAllWidgets(), $widgets));

        Cache::forever(self::CACHE_KEY_PREFIX . $userId, $validWidgets);
    }

    /** @return list<class-string> */
    private function getAllWidgets(): array
    {
        return $this->filterAuthorizedWidgets(array_values(array_merge(
            array_keys(self::HEADER_WIDGETS),
            array_keys(self::FOOTER_WIDGETS),
        )));
    }

    private function canViewWidget(string $widget): bool
    {
        if (! class_exists($widget)) {
            return false;
        }

        if (! method_exists($widget, 'canView')) {
            return true;
        }

        /** @var class-string $widget */
        return $widget::canView();
    }

    private function isWidgetsMode(): bool
    {
        return request()->boolean('widgets');
    }
}
