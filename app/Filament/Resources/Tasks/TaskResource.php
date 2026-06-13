<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\Pages\ViewTask;
use App\Filament\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Tâches';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'titre';

    public static function getNavigationGroup(): ?string
    {
        return 'Taches et Collaboration';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasAnyPermission([
                'collaboration.tasks.view',
                'collaboration.tasks.create',
                'collaboration.tasks.assign',
                'collaboration.tasks.update',
                'collaboration.tasks.close',
            ])
        );
    }

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('titre')->label('Titre')->columnSpanFull(),
            TextEntry::make('description')->label('Description')->columnSpanFull(),
            TextEntry::make('assignee.name')->label('Assigné à'),
            TextEntry::make('assigner.name')->label('Créé par'),
            TextEntry::make('priority')->label('Priorité')->badge(),
            TextEntry::make('status')
                ->label('Statut')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'todo'      => 'À faire',
                    'doing'     => 'En cours',
                    'done'      => 'Terminé',
                    'cancelled' => 'Annulé',
                    default     => $state,
                })
                ->badge(),
            TextEntry::make('due_date')->label('Échéance')->date('d/m/Y'),
            TextEntry::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i'),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'edit'   => EditTask::route('/{record}/edit'),
            'view'   => ViewTask::route('/{record}'),
        ];
    }
}
