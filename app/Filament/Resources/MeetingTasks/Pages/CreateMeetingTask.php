<?php

namespace App\Filament\Resources\MeetingTasks\Pages;

use App\Filament\Resources\MeetingTasks\MeetingTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeetingTask extends CreateRecord
{
    protected static string $resource = MeetingTaskResource::class;
}
