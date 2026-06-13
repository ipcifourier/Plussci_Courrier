@php
    $user = auth()->user();
    $roles = $user ? $user->getRoleNames() : collect();
    $permissions = $user ? $user->getAllPermissions()->pluck('name') : collect();
    $menus = \App\Filament\Components\DynamicMenu::getVisibleMenus();
    var_dump($menus);
@endphp

<!DOCTYPE html>
<html>
<head>
    <title>Debug Menu</title>
</head>
<body>
    @include('filament.components.dynamic-menu')
</body>
</html>