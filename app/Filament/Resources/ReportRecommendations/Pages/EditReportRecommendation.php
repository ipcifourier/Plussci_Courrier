<?php

namespace App\Filament\Resources\ReportRecommendations\Pages;

use App\Filament\Resources\ReportRecommendations\ReportRecommendationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportRecommendation extends EditRecord
{
    protected static string $resource = ReportRecommendationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
