<?php

namespace App\Filament\Resources\MeetingTasks\Pages;

use App\Filament\Resources\MeetingTasks\MeetingTaskResource;
use App\Filament\Resources\MeetingTasks\Widgets\MeetingTasksStatsWidget;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeetingTasks extends ListRecords
{
    protected static string $resource = MeetingTaskResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            MeetingTasksStatsWidget::class,
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
                    ['resource' => 'meeting-tasks', 'format' => 'csv'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),

            Action::make('export_xlsx')
                ->label('Exporter XLSX')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->url(fn (): string => route('agenda.export', array_merge(
                    ['resource' => 'meeting-tasks', 'format' => 'xlsx'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
