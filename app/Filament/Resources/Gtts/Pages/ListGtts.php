<?php

namespace App\Filament\Resources\Gtts\Pages;

use App\Filament\Resources\Gtts\GttResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
class ListGtts extends ListRecords
{
    protected static string $resource = GttResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(function (): bool {
                    $user = Auth::user();

                    return $user instanceof User
                        && ($user->hasRole('Super Admin') || $user->hasPermissionTo('admin.roles.manage'));
                }),
        ];
    }
}
