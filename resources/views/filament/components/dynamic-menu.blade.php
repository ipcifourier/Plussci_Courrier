@php
    $user = auth()->user();
    $roles = $user ? $user->getRoleNames() : collect();
    $permissions = $user ? $user->getAllPermissions()->pluck('name') : collect();
    $menus = \App\Filament\Components\DynamicMenu::getVisibleMenus(); // affichage dynamique par permissions
@endphp

<div class="p-4 bg-white rounded shadow" style="color: #222 !important;">
    <h3 class="text-lg font-bold mb-2">Menus dynamiques selon les permissions</h3>
    @php
        use Illuminate\Support\Facades\DB;
        $user = auth()->user();
        $roles = $user ? $user->getRoleNames() : collect();
        $permissions = $user ? $user->getAllPermissions()->pluck('name') : collect();
        $menus = \App\Filament\Components\DynamicMenu::getVisibleMenus(); // affichage dynamique par permissions
        echo '<div style="color:green">Utilisateur connecté : ' . ($user ? $user->email : 'aucun') . ' (ID: ' . ($user ? $user->id : '-') . ')</div>';
        if ($user) {
            echo '<div>Guard utilisé (utilisateur) : ' . (property_exists($user, 'guard_name') ? $user->guard_name : 'web (par défaut)') . '</div>';
            echo '<div>Rôles : ' . $roles->join(', ') . '</div>';
            // Affichage du guard_name et ID SQL pour chaque rôle
            if ($roles->count()) {
                echo '<div>Guard/ID des rôles : ';
                foreach ($roles as $roleName) {
                    $role = Spatie\Permission\Models\Role::where("name", $roleName)->first();
                    echo $roleName . ' [guard: ' . ($role ? $role->guard_name : 'inconnu') . ', id: ' . ($role ? $role->id : '-') . '] ';
                }
                echo '</div>';
            }
            echo '<div>Permissions : ' . $permissions->join(', ') . '</div>';
            // Affichage du guard_name et ID SQL pour chaque permission
            if ($permissions->count()) {
                echo '<div>Guard/ID des permissions : ';
                foreach ($permissions as $permName) {
                    $perm = Spatie\Permission\Models\Permission::where("name", $permName)->first();
                    echo $permName . ' [guard: ' . ($perm ? $perm->guard_name : 'inconnu') . ', id: ' . ($perm ? $perm->id : '-') . '] ';
                }
                echo '</div>';
            }
            // Affichage des IDs SQL liés (model_has_roles, model_has_permissions)
            $roleLinks = DB::table('model_has_roles')->where('model_type', 'App\\Models\\User')->where('model_id', $user->id)->pluck('role_id');
            echo '<div>model_has_roles (role_id): ' . $roleLinks->implode(', ') . '</div>';
            $permLinks = DB::table('model_has_permissions')->where('model_type', 'App\\Models\\User')->where('model_id', $user->id)->pluck('permission_id');
            echo '<div>model_has_permissions (permission_id): ' . $permLinks->implode(', ') . '</div>';
        }
        // Comparaison brute
        echo '<div style="color:blue"><strong>Comparaison brute :</strong><br>';
        foreach (\App\Filament\Components\DynamicMenu::$menus as $menu => $perm) {
            echo $menu . ' (' . $perm . ') → ' . (in_array($perm, $permissions->toArray()) ? '<span style="color:green">OK</span>' : '<span style="color:red">NON</span>') . '<br>';
        }
        echo '</div>';
    @endphp
    <ul class="list-disc ml-6">
        @forelse($menus as $menu)
            <li>{{ $menu }}</li>
        @empty
            <div class="text-red-600">Aucun menu visible pour cet utilisateur.</div>
        @endforelse
    </ul>
</div>
