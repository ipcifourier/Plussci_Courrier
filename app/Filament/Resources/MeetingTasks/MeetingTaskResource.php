<?php

namespace App\Filament\Resources\MeetingTasks;

use App\Filament\Resources\MeetingTasks\Pages\CreateMeetingTask;
use App\Filament\Resources\MeetingTasks\Pages\EditMeetingTask;
use App\Filament\Resources\MeetingTasks\Pages\ListMeetingTasks;
use App\Filament\Resources\MeetingTasks\Schemas\MeetingTaskForm;
use App\Filament\Resources\MeetingTasks\Tables\MeetingTasksTable;
use App\Models\MeetingTask;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MeetingTaskResource extends Resource
{
    protected static ?string $model = MeetingTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Diligences de reunion';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'Agenda';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasAnyPermission([
                'agenda.viewAny',
                'agenda.view',
                'agenda.create',
                'agenda.update',
                'agenda.delete',
                'agenda.diligences.manage',
            ])
        );
    }

    public static function form(Schema $schema): Schema
    {
        return MeetingTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeetingTasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetingTasks::route('/'),
            'create' => CreateMeetingTask::route('/create'),
            'edit' => EditMeetingTask::route('/{record}/edit'),
        ];
    }
}
