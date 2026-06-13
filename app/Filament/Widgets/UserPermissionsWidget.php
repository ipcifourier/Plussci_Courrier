<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class UserPermissionsWidget extends Widget
{
    protected string $view = 'filament.widgets.user-permissions-widget';

    protected static ?int $sort = 99;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user() instanceof User;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            return ['roles' => [], 'permissions' => []];
        }

        $freshUser = User::query()
            ->with(['roles', 'permissions'])
            ->find($user->id);

        if (! $freshUser instanceof User) {
            return ['roles' => [], 'permissions' => []];
        }

        $roles = $freshUser->getRoleNames()->sort()->values()->all();

        $permissions = $freshUser->getAllPermissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        return compact('roles', 'permissions');
    }
}
