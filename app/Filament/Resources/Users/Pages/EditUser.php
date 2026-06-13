<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GttRoleSyncService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['roles'] = $this->record->getRoleNames()->toArray();
        // Force le champ mot de passe à être vide lors de l'édition
        $data['password'] = '';
        $data['password_confirmation'] = '';
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si le champ mot de passe ou confirmation est vide, on les retire du tableau pour éviter la validation
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['last_password_changed_at'] = now();
        }
        if (empty($data['password_confirmation'])) {
            unset($data['password_confirmation']);
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $roles = $this->form->getState()['roles'] ?? null;
        if ($roles !== null && !empty($roles)) {
            // Remove 'Lecteur Courrier' if 'GTT Responsable' is present
            if (in_array('GTT Responsable', $roles)) {
                $roles = array_filter($roles, fn($r) => $r !== 'Lecteur Courrier');
            }
            $this->record->syncRoles($roles);
        }

        app(GttRoleSyncService::class)->syncUserResponsibility($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('change_password')
                ->label('Changer le mot de passe')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->modalHeading('Changer le mot de passe')
                ->form([
                    TextInput::make('new_password')
                        ->label('Nouveau mot de passe')
                        ->password()
                        ->required()
                        ->rule(Password::min(12)->letters()->mixedCase()->numbers()->symbols())
                        ->confirmed(),
                    TextInput::make('new_password_confirmation')
                        ->label('Confirmation')
                        ->password()
                        ->required()
                        ->dehydrated(false),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'password' => $data['new_password'],
                        'last_password_changed_at' => now(),
                    ]);

                    app(AuditLogger::class)->log(
                        action: 'user.password_changed',
                        entity: $this->record,
                        meta: ['changed_by' => Auth::id()]
                    );

                    Notification::make()
                        ->title('Mot de passe modifié avec succès')
                        ->success()
                        ->send();
                })
                ->visible(function (): bool {
                    $currentUser = Auth::user();

                    return $currentUser instanceof User
                        && $currentUser->can('update', $this->record);
                }),

            DeleteAction::make()
                ->visible(function (): bool {
                    $currentUser = Auth::user();

                    return Auth::id() !== $this->record->id
                        && $currentUser instanceof User
                        && $currentUser->hasRole('Super Admin');
                }),
        ];
    }
}
