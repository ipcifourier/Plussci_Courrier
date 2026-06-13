<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * AD4 — Enregistre les connexions/déconnexions dans le journal d'audit.
 */
class LogAuthEventListener
{
    public function __construct(private readonly AuditLogger $auditLogger, private readonly Request $request)
    {
    }

    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        // Update last_login_at on the user
        if (method_exists($user, 'update')) {
            $user->update(['last_login_at' => now()]);
        }

        /** @var Model|null $userModel */
        $userModel = $user instanceof Model ? $user : null;

        $this->auditLogger->log(
            action: 'auth.login',
            entity: $userModel,
            after: [
                'ip'         => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ],
        );
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        /** @var Model|null $userModel */
        $userModel = $event->user instanceof Model ? $event->user : null;

        $this->auditLogger->log(
            action: 'auth.logout',
            entity: $userModel,
            after: [
                'ip' => $this->request->ip(),
            ],
        );
    }
}
