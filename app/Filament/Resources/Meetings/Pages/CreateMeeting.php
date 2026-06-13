<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use App\Notifications\MeetingParticipantInvitedNotification;
use Filament\Resources\Pages\CreateRecord;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pre-fill starts_at from URL query param
        if (request()->filled('starts_at')) {
            $data['starts_at'] = request()->query('starts_at');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $meeting = $this->record;

        // Notify all participants
        foreach ($meeting->participants as $participant) {
            if ($participant->id !== $meeting->facilitator_id) {
                $participant->notify(new MeetingParticipantInvitedNotification($meeting));
            }
        }
    }
}
