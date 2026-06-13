<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceInactivityTimeout
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

        $timeoutMinutes = (int) ($user->inactivity_timeout_minutes ?: config('session.lifetime', 120));
        $timeoutMinutes = max(5, min($timeoutMinutes, 480));

        $now = now()->timestamp;
        $lastActivityAt = (int) $request->session()->get('last_activity_at', $now);

        if (($now - $lastActivityAt) > ($timeoutMinutes * 60)) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return new JsonResponse([
                    'message' => 'Session expirée après inactivité.',
                ], 401);
            }

            /** @var RedirectResponse $redirect */
            $redirect = redirect()
                ->route('filament.admin.auth.login')
                ->with('status', 'Session expirée après inactivité.');

            return $redirect;
        }

        $request->session()->put('last_activity_at', $now);

        return $next($request);
    }
}
