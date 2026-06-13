<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(function (): bool {
                    $user = Auth::user();

                    return $user instanceof User
                        && $user->can('update', $this->record);
                }),

            Action::make('change_password')
                ->label('Changer le mot de passe')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->modalHeading('Changer le mot de passe')
                ->form([
                    TextInput::make('new_password')
                        ->label('Nouveau mot de passe')
                        ->password()
                        ->revealable()
                        ->required()
                        ->rule(Password::min(12)->letters()->mixedCase()->numbers()->symbols())
                        ->confirmed()
                        ->helperText('Minimum 12 caractères avec majuscule, minuscule, chiffre et symbole.'),
                    TextInput::make('new_password_confirmation')
                        ->label('Confirmation')
                        ->password()
                        ->revealable()
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
                    $user = Auth::user();

                    return $user instanceof User
                        && $user->can('update', $this->record);
                }),
        ];
    }

    /** AD4 — Affiche les dernières connexions de l'utilisateur. */
    public function getRecentLogins(): \Illuminate\Support\Collection
    {
        return AuditLog::where('actor_id', $this->record->id)
            ->where('action', 'auth.login')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['created_at', 'after_json']);
    }
}
