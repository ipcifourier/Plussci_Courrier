<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\User;
use App\Notifications\AppointmentAssignedNotification;
use Filament\Resources\Pages\CreateRecord;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pre-fill starts_at from URL query param
        if (request()->filled('starts_at')) {
            $data['starts_at'] = request()->query('starts_at');
        }

        // Pre-fill type from URL query param
        if (request()->filled('type') && in_array(request()->query('type'), ['rendez_vous', 'diligence'])) {
            $data['type'] = request()->query('type');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $appointment = $this->record;

        // Notify the assigned user if different from the creator
        if ($appointment->assigned_to && $appointment->assigned_to !== $appointment->created_by) {
            $assignee = User::find($appointment->assigned_to);
            $assignee?->notify(new AppointmentAssignedNotification($appointment));
        }
    }
}
