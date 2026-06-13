<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Filament\Resources\Visits\VisitResource;
use App\Filament\Resources\Visits\Widgets\VisitsStatsWidget;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVisits extends ListRecords
{
    protected static string $resource = VisitResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            VisitsStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Exporter CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn (): string => route('agenda.export', array_merge(
                    ['resource' => 'visits', 'format' => 'csv'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),

            Action::make('export_xlsx')
                ->label('Exporter XLSX')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->url(fn (): string => route('agenda.export', array_merge(
                    ['resource' => 'visits', 'format' => 'xlsx'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
