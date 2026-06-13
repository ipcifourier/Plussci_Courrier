<?php

namespace App\Filament\Resources\ReportRecommendations\Pages;

use App\Filament\Resources\ReportRecommendations\ReportRecommendationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReportRecommendations extends ListRecords
{
    protected static string $resource = ReportRecommendationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
