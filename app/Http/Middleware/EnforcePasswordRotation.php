<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforcePasswordRotation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $rotationDays = (int) config('security.password_rotation_days', 90);

        if ($rotationDays <= 0) {
            return $next($request);
        }

        // Existing accounts may not have this timestamp yet; avoid forcing a hard lock.
        if ($user->last_password_changed_at === null) {
            return $next($request);
        }

        $mustRotate = $user->last_password_changed_at->lt(now()->subDays($rotationDays));

        if (! $mustRotate) {
            return $next($request);
        }

        if ($request->routeIs('filament.admin.pages.mon-profil')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => 'Changement de mot de passe requis.',
            ], 423);
        }

        /** @var RedirectResponse $redirect */
        $redirect = redirect()
            ->route('filament.admin.pages.mon-profil')
            ->with('warning', 'Votre mot de passe a expiré. Veuillez le changer pour continuer.');

        return $redirect;
    }
}
