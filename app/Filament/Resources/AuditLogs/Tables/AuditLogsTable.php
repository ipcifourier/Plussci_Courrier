<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('actor.name')->label('Acteur')->placeholder('Système')->searchable(),
                TextColumn::make('action')->label('Action')->badge()->searchable()->sortable(),
                TextColumn::make('entity_type')->label('Entité')->placeholder('-')->searchable()->toggleable(),
                TextColumn::make('entity_id')->label('ID')->placeholder('-')->toggleable(),
                TextColumn::make('ip_address')->label('IP')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options(fn (): array => AuditLog::query()
                        ->select('action')
                        ->whereNotNull('action')
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->all()),
                SelectFilter::make('actor_id')
                    ->label('Acteur')
                    ->relationship('actor', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('entity_type')
                    ->label('Type entité')
                    ->options(fn (): array => AuditLog::query()
                        ->select('entity_type')
                        ->whereNotNull('entity_type')
                        ->distinct()
                        ->orderBy('entity_type')
                        ->pluck('entity_type', 'entity_type')
                        ->all()),
                Filter::make('date_range')
                    ->label('Période')
                    ->form([
                        DatePicker::make('date_from')->label('Date début'),
                        DatePicker::make('date_to')->label('Date fin'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(filled($data['date_from'] ?? null), fn ($query) => $query->whereDate('created_at', '>=', $data['date_from']))
                            ->when(filled($data['date_to'] ?? null), fn ($query) => $query->whereDate('created_at', '<=', $data['date_to']));
                    }),
                // AU6 — Recherche dans les données JSON avant/après
                Filter::make('json_field')
                    ->label('Champ modifié')
                    ->form([
                        FormTextInput::make('field_name')
                            ->label('Nom du champ')
                            ->placeholder('ex: statut, objet, reference…'),
                        FormTextInput::make('field_value')
                            ->label('Valeur (optionnel)')
                            ->placeholder('ex: Traité'),
                    ])
                    ->query(function ($query, array $data): mixed {
                        $field = trim($data['field_name'] ?? '');
                        $value = trim($data['field_value'] ?? '');

                        if ($field === '') {
                            return $query;
                        }

                        return $query->where(function ($q) use ($field, $value): void {
                            if ($value !== '') {
                                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(before_json, ?)) LIKE ?", ['$.' . $field, '%' . $value . '%'])
                                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(after_json, ?)) LIKE ?", ['$.' . $field, '%' . $value . '%']);
                            } else {
                                $q->whereRaw("JSON_EXTRACT(before_json, ?) IS NOT NULL", ['$.' . $field])
                                  ->orWhereRaw("JSON_EXTRACT(after_json, ?) IS NOT NULL", ['$.' . $field]);
                            }
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (filled($data['field_name'] ?? null)) {
                            $label = 'Champ: ' . $data['field_name'];
                            if (filled($data['field_value'] ?? null)) {
                                $label .= ' = ' . $data['field_value'];
                            }
                            $indicators[] = $label;
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
