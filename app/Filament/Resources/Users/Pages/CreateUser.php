<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\GttRoleSyncService;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['password'])) {
            $data['last_password_changed_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $roles = $this->form->getState()['roles'] ?? [];
        // Remove 'Lecteur Courrier' if 'GTT Responsable' is present
        if (in_array('GTT Responsable', $roles)) {
            $roles = array_filter($roles, fn($r) => $r !== 'Lecteur Courrier');
        }
        if (! empty($roles)) {
            $this->record->syncRoles($roles);
        }

        app(GttRoleSyncService::class)->syncUserResponsibility($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
