<?php

namespace App\Filament\Resources\Courriers\Pages;

use App\Filament\Resources\Courriers\CourrierResource;
use App\Filament\Resources\Courriers\Widgets\CourriersListStatsWidget;
use App\Models\User;
use App\Services\CourrierSmartSearchService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListCourriers extends ListRecords
{
    protected static string $resource = CourrierResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CourriersListStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        // Premier niveau : bouton créer + actions principales
        $mainActions = [
            CreateAction::make()
                ->label('Créer un courrier')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->extraAttributes([
                    'style' => 'background-color: #FF9800 !important; color: #222 !important; border: 1px solid #FF9800 !important; font-weight: bold;',
                ])
                ->visible(true),
            Action::make('mes_courriers_a_traiter')
                ->label('Mes courriers à traiter')
                ->icon('heroicon-o-inbox-stack')
                ->color('warning')
                ->extraAttributes([
                    'style' => 'background-color: #FFD600 !important; color: #222 !important; border: 1px solid #FFD600 !important; font-weight: bold;',
                ])
                ->visible(true)
                ->url(fn (): string => CourrierResource::getUrl('mes-courriers-a-traiter')),
            Action::make('a_approuver')
                ->label('À approuver')
                ->icon('heroicon-o-check-badge')
                ->color('warning')
                ->url(fn (): string => CourrierResource::getUrl('a-approuver')),
            Action::make('a_signer')
                ->label('À signer')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->url(fn (): string => CourrierResource::getUrl('a-signer')),
        ];

        // Second niveau : actions secondaires
        $secondaryActions = [
            Action::make('smart_search')
                ->label('Recherche intelligente')
                ->icon('heroicon-o-magnifying-glass-circle')
                ->color('info')
                ->form([
                    Forms\Components\TextInput::make('query')
                        ->label('Demande')
                        ->placeholder('Ex: courriers entrants urgents en retard 2026')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $result = app(CourrierSmartSearchService::class)->buildListQuery($data['query']);
                    Notification::make()
                        ->title('Interprétation de la recherche')
                        ->body($result['summary'])
                        ->info()
                        ->send();
                    $base = CourrierResource::getUrl('index');
                    $query = $result['query'];
                    return redirect()->to($base . '?' . http_build_query($query));
                }),
        ];

        // Retourner toutes les actions dans un seul tableau (Filament attend un tableau plat)
        return array_merge($mainActions, $secondaryActions);
    }
}
