<?php

namespace App\Filament\Resources\ReportTemplates\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Modele')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Categorie')
                    ->placeholder('-')
                    ->sortable(),

                IconColumn::make('is_validated')
                    ->label('Valide PLUSS')
                    ->boolean(),

                TextColumn::make('reports_count')
                    ->label('Rapports lies')
                    ->counts('reports')
                    ->badge(),

                TextColumn::make('updated_at')
                    ->label('Maj')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('title');
    }
}
