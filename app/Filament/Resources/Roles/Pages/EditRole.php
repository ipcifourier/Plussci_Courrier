<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Services\GttRoleSyncService;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return RoleForm::fillPermissions($data, $this->record);
    }

    protected function afterSave(): void
    {
        $permissions = app(GttRoleSyncService::class)->normalizeRolePermissions(
            (string) $this->record->name,
            RoleForm::collectPermissions($this->form->getState()),
        );

        app(GttRoleSyncService::class)->ensureRoleSetup((string) $this->record->name);

        $this->record->syncPermissions($permissions);
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            DeleteAction::make()
                ->hidden(fn (): bool => $this->record->name === 'Super Admin'),
        ];
        // Display rights for admin if GTT Responsable
        if ($this->record->name === 'GTT Responsable') {
            $actions[] = Action::make('show_gtt_rights')
                ->label('Voir les droits GTT Responsable')
                ->color('info')
                ->modalHeading('Droits attribués au GTT Responsable')
                ->modalContent(view('filament.modals.gtt-responsable-rights'));
        }
        return $actions;
    }
}
