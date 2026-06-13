<?php

namespace App\Filament\Components;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class DynamicMenu
{
    /**
     * Correspondance menu => permission
     */
    public static array $menus = [
        'GED'                     => 'ged.documents.view',
        'Utilisateurs'            => 'admin.users.view',
        'Rôles & Permissions'     => 'admin.roles.manage',
        'Départements'            => 'admin.roles.manage',
        'Audit et traçabilité'    => 'audit.view',
        'Rapports'                => 'reports.viewAny',
        'Tâches'                  => 'collaboration.tasks.view',
        'Structures'              => 'admin.roles.manage',
        'GTT'                     => 'gtt.documents.view',
        'Paramètres GED'          => 'admin.roles.manage',
        'Catégories rapports'     => 'reports.templates.manage',
        'Modèles rapports PLUSS'  => 'reports.templates.manage',
        'Journal d\'audit'        => 'audit.view',
    ];

    /**
     * @return array<int, string>
     * @phpstan-return list<string>
     */
    public static function getVisibleMenus(): array
    {

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        // Debug : afficher le guard courant
        if (isset($_GET['debug_menu'])) {
            $guard = null;
            if (property_exists($user, 'guard_name')) {
                $guard = $user->guard_name;
            }
            echo '<div style="color:#00a">[DEBUG] Guard courant utilisé pour Auth::user() : <b>' . ($guard ?? 'web (par défaut)') . '</b></div>';
        }

        // DEBUG
        if (isset($_GET['debug_menu'])) {
            echo '<div style="background:#ffe;border:1px solid #cc0;padding:10px;">';
            echo '<b>Guard utilisateur :</b> ' . ($user->guard_name ?? 'web (par défaut)') . '<br>';
            // @phpstan-ignore-next-line
            // noinspection PhpUndefinedMethodInspection
            $allPerms = [];
            if (is_object($user) && method_exists($user, 'getAllPermissions')) {
                // @phpstan-ignore-next-line
                $perms = $user->getAllPermissions();
                if ($perms instanceof \Illuminate\Support\Collection) {
                    $allPerms = $perms->pluck('name')->toArray();
                }
            }
            echo '<b>Permissions utilisateur :</b> ' . implode(', ', $allPerms) . '<br>';
            foreach (self::$menus as $menu => $permission) {
                // @phpstan-ignore-next-line
                $can = (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) ? 'OUI' : 'NON';
                echo $menu . ' (' . $permission . ') → can() = <b>' . $can . '</b><br>';
            }
            echo '</div>';
        }

        if (!$user) {
            return [];
        }

        // Super Admin : tout voir
        // @phpstan-ignore-next-line
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return array_keys(self::$menus);
        }

        $visible = [];
        foreach (self::$menus as $menu => $permission) {
            // @phpstan-ignore-next-line
            if ($user && method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
                $visible[] = $menu;
            }
        }
        return $visible;
    }
    

}
