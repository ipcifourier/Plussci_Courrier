<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use App\Filament\Resources\Meetings\Widgets\MeetingsStatsWidget;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeetings extends ListRecords
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            MeetingsStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('synthese_hebdo_pdf')
                ->label('Synthèse hebdo PDF')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->url(fn (): string => route('agenda.synthese.pdf', request()->query()))
                ->openUrlInNewTab(),

            Action::make('export_csv')
                ->label('Exporter CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn (): string => route('agenda.export', array_merge(
                    ['resource' => 'meetings', 'format' => 'csv'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),

            Action::make('export_xlsx')
                ->label('Exporter XLSX')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->url(fn (): string => route('agenda.export', array_merge(
                    ['resource' => 'meetings', 'format' => 'xlsx'],
                    request()->query(),
                )))
                ->openUrlInNewTab(),
            // A3 — Export iCal
            Action::make('export_ical')
                ->label('Export iCal')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->url(fn (): string => route('agenda.ical', ['type' => 'meetings']))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
