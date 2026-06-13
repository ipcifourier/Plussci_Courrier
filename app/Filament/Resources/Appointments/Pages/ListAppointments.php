<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Widgets\AppointmentsStatsWidget;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentsStatsWidget::class,
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
                    ['resource' => 'appointments', 'format' => 'csv'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),

            Action::make('export_xlsx')
                ->label('Exporter XLSX')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->url(fn (): string => route('agenda.export', array_merge(
                    ['resource' => 'appointments', 'format' => 'xlsx'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),
            // A3 — Export iCal
            Action::make('export_ical')
                ->label('Export iCal')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->url(fn (): string => route('agenda.ical', ['type' => 'appointments']))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
