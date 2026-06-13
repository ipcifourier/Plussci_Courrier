<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('Photo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? '?') . '&color=7C3AED&background=EDE9FE')
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('departement.nom')
                    ->label('Département')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('poste')
                    ->label('Poste')
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->separator(','),

                TextColumn::make('is_active')
                    ->label('Statut')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Actif' : 'Désactivé')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rôle')
                    ->options(fn () => Role::where('guard_name', 'web')->orderBy('name')->pluck('name', 'name')->toArray())
                    ->query(fn ($query, array $data) => filled($data['value'])
                        ? $query->whereHas('roles', fn ($q) => $q->where('name', $data['value']))
                        : $query
                    ),

                SelectFilter::make('departement_id')
                    ->label('Département')
                    ->relationship('departement', 'nom')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('is_active')
                    ->label('Statut')
                    ->options([
                        '1' => 'Actifs',
                        '0' => 'Désactivés',
                    ])
                    ->query(fn ($query, array $data) => array_key_exists('value', $data) && $data['value'] !== null && $data['value'] !== ''
                        ? $query->where('is_active', (bool) $data['value'])
                        : $query
                    ),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('deactivate')
                    ->label('Désactiver')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => (bool) $record->is_active && Auth::id() !== $record->id)
                    ->action(function (User $record): void {
                        $record->update(['is_active' => false]);

                        Notification::make()
                            ->title('Utilisateur désactivé')
                            ->success()
                            ->send();
                    }),
                Action::make('activate')
                    ->label('Activer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => ! (bool) $record->is_active)
                    ->action(function (User $record): void {
                        $record->update(['is_active' => true]);

                        Notification::make()
                            ->title('Utilisateur activé')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->visible(function (User $record): bool {
                        $currentUser = Auth::user();

                        return Auth::id() !== $record->id
                            && $currentUser instanceof User
                            && $currentUser->hasRole('Super Admin');
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_activate')
                        ->label('Activer la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $activated = 0;

                            foreach ($records as $record) {
                                if ((bool) $record->is_active) {
                                    continue;
                                }

                                $record->update(['is_active' => true]);
                                $activated++;
                            }

                            Notification::make()
                                ->title($activated > 0
                                    ? "{$activated} utilisateur(s) activé(s)"
                                    : 'Aucun utilisateur à activer')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('bulk_deactivate')
                        ->label('Désactiver la sélection')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $deactivated = 0;
                            $skippedSelf = 0;
                            $currentUserId = Auth::id();

                            foreach ($records as $record) {
                                if ($currentUserId === $record->id) {
                                    $skippedSelf++;
                                    continue;
                                }

                                if (! (bool) $record->is_active) {
                                    continue;
                                }

                                $record->update(['is_active' => false]);
                                $deactivated++;
                            }

                            $message = $deactivated > 0
                                ? "{$deactivated} utilisateur(s) désactivé(s)"
                                : 'Aucun utilisateur à désactiver';

                            if ($skippedSelf > 0) {
                                $message .= ' (votre compte a été ignoré)';
                            }

                            Notification::make()
                                ->title($message)
                                ->success()
                                ->send();
                        }),
                    // AD3 — Forcer reset MDP en masse
                    BulkAction::make('bulk_force_password_reset')
                        ->label('Forcer reset MDP')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Forcer la réinitialisation du mot de passe')
                        ->modalDescription('Les utilisateurs sélectionnés devront changer leur mot de passe à leur prochaine connexion.')
                        ->action(function (Collection $records): void {
                            $count = 0;

                            foreach ($records as $record) {
                                $record->update(['last_password_changed_at' => null]);
                                $count++;
                            }

                            Notification::make()
                                ->title("{$count} utilisateur(s) marqués pour reset MDP")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
