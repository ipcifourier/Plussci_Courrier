<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Schemas\PermissionHelper;
use App\Services\GttRoleSyncService;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ne garder que les champs du modèle Role
        return [
            'name' => $data['name'] ?? null,
            'guard_name' => $data['guard_name'] ?? 'web',
        ];
    }

    protected function afterCreate(): void
    {
        $permissions = app(GttRoleSyncService::class)->normalizeRolePermissions(
            (string) $this->record->name,
            RoleForm::collectPermissions($this->form->getState()),
        );

        app(GttRoleSyncService::class)->ensureRoleSetup((string) $this->record->name);

        if (! empty($permissions)) {
            $this->record->syncPermissions($permissions);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
