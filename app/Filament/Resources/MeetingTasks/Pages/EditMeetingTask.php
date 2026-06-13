<?php

namespace App\Filament\Resources\MeetingTasks\Pages;

use App\Filament\Resources\MeetingTasks\MeetingTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMeetingTask extends EditRecord
{
    protected static string $resource = MeetingTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
