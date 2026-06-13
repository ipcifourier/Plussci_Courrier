<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class RefreshPermissionCache
{
    public function __construct(private readonly PermissionRegistrar $registrar) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->registrar->forgetCachedPermissions();

        return $next($request);
    }
}
