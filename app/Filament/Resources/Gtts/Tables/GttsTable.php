<?php

namespace App\Filament\Resources\Gtts\Tables;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GttsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('GTT')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('responsable')
                    ->label('Responsable')
                    ->formatStateUsing(fn ($state, $record): string => $record->responsableUser?->name ?? ($state ?: '—'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bureau_members_count')
                    ->label('Membres')
                    ->counts('bureauMembers')
                    ->badge()
                    ->sortable(),
                TextColumn::make('documents_count')
                    ->label('Documents liés')
                    ->counts('documents')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make()
                    ->visible(function ($record): bool {
                        $user = Auth::user();

                        return $user instanceof User && $record->canBeManagedBy($user);
                    }),
                DeleteAction::make()
                    ->visible(function ($record): bool {
                        $user = Auth::user();

                        return $user instanceof User
                            && ($user->hasRole('Super Admin') || $user->hasPermissionTo('admin.roles.manage'));
                    })
                    ->before(function ($record, DeleteAction $action): void {
                        if ($record->documents()->exists()) {
                            Notification::make()
                                ->title('Suppression impossible')
                                ->body('Ce GTT est lié à des documents.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
                \Filament\Actions\Action::make('voir')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.gtts.edit', ['record' => $record->id]))
                    ->color('info')
                    ->visible(function ($record): bool {
                        $user = Auth::user();

                        return $user instanceof User && $record->canBeViewedBy($user);
                    }),
                \Filament\Actions\Action::make('detail_membres')
                    ->label('Détail Membres')
                    ->icon('heroicon-o-users')
                    ->url(fn ($record) => route('filament.admin.resources.gtts.edit', ['record' => $record->id]))
                    ->color('primary')
                    ->visible(function ($record): bool {
                        $user = Auth::user();

                        return $user instanceof User && $record->canBeViewedBy($user);
                    }),
            ])
            // Export retiré, service non défini
            ->defaultSort('name');
    }
}
