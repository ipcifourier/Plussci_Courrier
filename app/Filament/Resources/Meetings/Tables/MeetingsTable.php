<?php

namespace App\Filament\Resources\Meetings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Courrier;
use App\Services\AuditLogger;

class MeetingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Reunion')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('Debut')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Lieu')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('facilitator.name')
                    ->label('Animateur')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('tasks_count')
                    ->label('Diligences')
                    ->counts('tasks')
                    ->badge()
                    ->color('info'),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => 'Planifiee',
                        'held' => 'Tenue',
                        'cancelled' => 'Annulee',
                        'postponed' => 'Reportee',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'planned',
                        'success' => 'held',
                        'danger' => 'cancelled',
                        'warning' => 'postponed',
                    ])
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'planned' => 'Planifiee',
                        'held' => 'Tenue',
                        'cancelled' => 'Annulee',
                        'postponed' => 'Reportee',
                    ]),

                SelectFilter::make('facilitator_id')
                    ->label('Animateur')
                    ->relationship('facilitator', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('mes_reunions')
                    ->label('Mes reunions')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $sub): void {
                        $sub->where('facilitator_id', Auth::id())
                            ->orWhereHas('participants', fn (Builder $q): Builder => $q->where('users.id', Auth::id()));
                    }))
                    ->toggle(),

                Filter::make('semaine_en_cours')
                    ->label('Semaine en cours')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
                    )
                    ->toggle(),

                Filter::make('a_venir_7_jours')
                    ->label('A venir (7 jours)')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereBetween('starts_at', [now(), now()->copy()->addDays(7)])
                    )
                    ->toggle(),

                Filter::make('a_tenir_en_retard')
                    ->label('A tenir en retard')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('starts_at', '<', now())
                        ->whereIn('status', ['planned', 'postponed'])
                    )
                    ->toggle(),

                Filter::make('periode')
                    ->label('Periode')
                    ->form([
                        DatePicker::make('from')->label('Du')->native(false),
                        DatePicker::make('until')->label('Au')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('starts_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('starts_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                // A5 — Clôturer réunion / enregistrer présences
                Action::make('close_meeting')
                    ->label('Clôturer')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record): bool => in_array($record->status, ['planned', 'postponed']))
                    ->fillForm(fn ($record): array => [
                        'attendance' => $record->participants->mapWithKeys(
                            fn ($u) => [$u->id => $u->pivot->attendance_status ?? 'absent']
                        )->toArray(),
                    ])
                    ->form(fn ($record): array => [
                        CheckboxList::make('present_ids')
                            ->label('Participants présents')
                            ->options($record->participants->pluck('name', 'id')->toArray())
                            ->columns(2),
                    ])
                    ->action(function ($record, array $data): void {
                        $presentIds = $data['present_ids'] ?? [];

                        foreach ($record->participants as $participant) {
                            $status = in_array($participant->id, $presentIds) ? 'present' : 'absent';
                            DB::table('meeting_participants')
                                ->where('meeting_id', $record->id)
                                ->where('user_id', $participant->id)
                                ->update(['attendance_status' => $status]);
                        }

                        $record->update(['status' => 'held']);

                        app(AuditLogger::class)->log(
                            action: 'meeting.close',
                            entity: $record,
                            before: ['status' => 'planned'],
                            after: ['status' => 'held', 'presents' => count($presentIds)],
                        );
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Clôturer la réunion'),

                // A6 — Générer courrier de convocation
                Action::make('generate_convocation')
                    ->label('Convocation')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->form([
                        TextInput::make('objet')
                            ->label('Objet du courrier')
                            ->default(fn ($record): string => "Convocation — {$record->title}")
                            ->required()
                            ->maxLength(255),
                        Select::make('nature_courrier')
                            ->label('Nature')
                            ->options([
                                'Note de service' => 'Note de service',
                                'Invitation'      => 'Invitation',
                                'Convocation'     => 'Convocation',
                            ])
                            ->default('Convocation')
                            ->required()
                            ->native(false),
                    ])
                    ->action(function ($record, array $data): void {
                        $resume = "Réunion : {$record->title}\n"
                            . "Date : " . $record->starts_at->format('d/m/Y à H:i') . "\n"
                            . ($record->location ? "Lieu : {$record->location}\n" : '');

                        Courrier::create([
                            'objet'           => $data['objet'],
                            'type'            => 'Sortant',
                            'nature_courrier' => $data['nature_courrier'],
                            'resume'          => $resume,
                            'statut'          => 'En cours',
                            'user_id'         => Auth::id(),
                        ]);
                    })
                    ->successNotificationTitle('Courrier de convocation créé.')
                    ->modalHeading('Générer un courrier de convocation'),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('starts_at', 'desc');
    }
}
