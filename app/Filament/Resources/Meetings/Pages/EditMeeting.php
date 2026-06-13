<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMeeting extends EditRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_held')
                ->label('Marquer tenue')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => in_array($this->record->status, ['planned', 'postponed'], true))
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'held',
                    ]);

                    Notification::make()
                        ->title('Reunion marquee comme tenue')
                        ->success()
                        ->send();

                    $this->refreshFormData(array_keys($this->record->getAttributes()));
                }),

            Action::make('postpone_meeting')
                ->label('Reporter')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->visible(fn (): bool => in_array($this->record->status, ['planned', 'postponed'], true))
                ->form([
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Nouveau debut')
                        ->required()
                        ->seconds(false)
                        ->native(false),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Nouvelle fin')
                        ->nullable()
                        ->seconds(false)
                        ->native(false),
                ])
                ->fillForm(fn (): array => [
                    'starts_at' => $this->record->starts_at,
                    'ends_at' => $this->record->ends_at,
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'postponed',
                        'starts_at' => $data['starts_at'],
                        'ends_at' => $data['ends_at'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Reunion reportee')
                        ->success()
                        ->send();

                    $this->refreshFormData(array_keys($this->record->getAttributes()));
                }),

            Action::make('cancel_meeting')
                ->label('Annuler')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => in_array($this->record->status, ['planned', 'postponed'], true))
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'cancelled',
                    ]);

                    Notification::make()
                        ->title('Reunion annulee')
                        ->warning()
                        ->send();

                    $this->refreshFormData(array_keys($this->record->getAttributes()));
                }),

            DeleteAction::make(),
        ];
    }
}
