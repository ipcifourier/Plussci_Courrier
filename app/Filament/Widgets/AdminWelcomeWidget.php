<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AdminWelcomeWidget extends Widget
{
    protected string $view = 'filament.widgets.admin-welcome-widget';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User;
    }

    protected function getViewData(): array
    {
        $user = Auth::user();

        return [
            'userName' => $user?->name ?? 'Utilisateur',
            'now' => now(),
        ];
    }
}
