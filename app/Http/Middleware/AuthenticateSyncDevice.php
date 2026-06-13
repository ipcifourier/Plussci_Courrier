<?php

namespace App\Http\Middleware;

use App\Models\SyncDevice;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticateSyncDevice
{
    public function handle(Request $request, Closure $next): mixed
    {
        $plainToken = $this->extractToken($request);

        if (! $plainToken) {
            return new JsonResponse([
                'message' => 'Jeton de synchronisation manquant.',
            ], 401);
        }

        $device = SyncDevice::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('is_active', true)
            ->first();

        if (! $device || ! $device->user || ! $device->user->is_active) {
            return new JsonResponse([
                'message' => 'Jeton de synchronisation invalide ou inactif.',
            ], 401);
        }

        $device->forceFill([
            'last_seen_at' => now(),
        ])->save();

        $request->attributes->set('syncDevice', $device);
        $request->attributes->set('syncUser', $device->user);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $bearer = trim((string) $request->bearerToken());

        if ($bearer !== '') {
            return $bearer;
        }

        $header = trim((string) $request->header('X-Sync-Token'));

        return $header !== '' ? $header : null;
    }
}
