<?php

namespace App\Filament\Resources\Gtts\RelationManagers;

use App\Models\Gtt;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class BureauMembersRelationManager extends RelationManager
{
    protected function canExport(): bool
    {
        $user = Auth::user();
        $gtt = $this->getOwnerRecord();

        if (! $user instanceof User || ! $gtt instanceof Gtt) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        try {
            if ($user->hasPermissionTo('admin.roles.manage')) {
                return true;
            }

            return $gtt->canBeViewedBy($user);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    protected function canCreate(): bool
    {
        $user = Auth::user();
        $gtt = $this->getOwnerRecord();

        return $user instanceof User && $gtt instanceof Gtt && $gtt->canBeManagedBy($user);
    }

    protected function canEdit($record): bool
    {
        $user = Auth::user();
        $gtt = $this->getOwnerRecord();

        return $user instanceof User && $gtt instanceof Gtt && $gtt->canBeManagedBy($user);
    }

    protected function canDelete($record): bool
    {
        $user = Auth::user();
        $gtt = $this->getOwnerRecord();

        return $user instanceof User && $gtt instanceof Gtt && $gtt->canBeManagedBy($user);
    }

    protected static string $relationship = 'bureauMembers';

    protected static ?string $title = 'Membres du bureau';

    public function form(Schema $schema): Schema
    {
        return \App\Filament\Resources\BureauMembers\Schemas\BureauMemberForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Nom')->searchable()->sortable(),
                TextColumn::make('prenom')->label('Prénom')->searchable()->sortable(),
                TextColumn::make('fonction')->label('Fonction')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('telephone')->label('Téléphone')->searchable(),
                BadgeColumn::make('statut')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => $state ? 'Actif' : 'Inactif')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        '1' => 'Actifs',
                        '0' => 'Inactifs',
                    ])
                    ->query(fn ($query, array $data) => filled($data['value'])
                        ? $query->where('statut', $data['value'])
                        : $query
                    ),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                \Filament\Actions\Action::make('toggle_statut')
                    ->label('Activer/Désactiver')
                    ->icon('heroicon-o-check')
                    ->action(fn ($record) => $record->update(['statut' => !$record->statut])),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Créer Membre')
                    ->icon('heroicon-o-user-plus'),
                \Filament\Actions\Action::make('export_csv')
                    ->label('Exporter CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (): bool => $this->canExport())
                    ->url(fn (): string => route('gtts.bureau-members.export', array_merge(
                        ['gtt' => $this->getOwnerRecord()->getKey(), 'format' => 'csv'],
                        request()->query(),
                    )))
                    ->openUrlInNewTab(),
                \Filament\Actions\Action::make('export_xlsx')
                    ->label('Exporter XLSX')
                    ->icon('heroicon-o-table-cells')
                    ->visible(fn (): bool => $this->canExport())
                    ->url(fn (): string => route('gtts.bureau-members.export', array_merge(
                        ['gtt' => $this->getOwnerRecord()->getKey(), 'format' => 'xlsx'],
                        request()->query(),
                    )))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('nom');
    }
}
