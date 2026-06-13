<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SYSGEDShareJournalRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $title = 'SYSGED Share - Journal';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Journal des evenements de partage et co-edition')
            ->description('Trace des actions SYSGED Share sur ce document (partages, revocations, ouvertures editeur, sauvegardes OnlyOffice).')
            ->emptyStateHeading('Aucun evenement journalise')
            ->emptyStateDescription('Les actions effectuees via SYSGED Share apparaitront ici.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->headerActions([
                Action::make('refresh_journal')
                    ->label('Actualiser')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function (): void {
                    }),
            ])
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('actor.name')
                    ->label('Acteur')
                    ->placeholder('Systeme')
                    ->searchable(),

                TextColumn::make('meta_json')
                    ->label('Details')
                    ->formatStateUsing(function ($state): string {
                        if (! is_array($state) || $state === []) {
                            return '-';
                        }

                        $pairs = [];

                        foreach ($state as $key => $value) {
                            if (is_scalar($value) || $value === null) {
                                $pairs[] = $key . ': ' . (string) ($value ?? 'null');
                            }
                        }

                        return $pairs === [] ? '-' : implode(' | ', $pairs);
                    })
                    ->wrap()
                    ->limit(140),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
