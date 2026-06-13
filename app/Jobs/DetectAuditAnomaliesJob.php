<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * AU2 — Détecte les anomalies dans le journal d'audit (IP suspectes + utilisateurs
 * dont le volume d'actions dépasse les seuils configurés) et envoie une notification
 * Filament aux Super Admin.
 */
class DetectAuditAnomaliesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Seuil d'actions par IP sur 24 h avant alerte. */
    private const IP_THRESHOLD = 30;

    /** Seuil d'actions par utilisateur sur 1 h avant alerte. */
    private const USER_THRESHOLD_HOURLY = 50;

    public function handle(): void
    {
        $now      = Carbon::now();
        $since24h = $now->copy()->subDay();
        $since1h  = $now->copy()->subHour();

        $anomalies = [];

        // — IP suspectes (plus de 30 actions en 24 h) —
        AuditLog::query()
            ->select('ip_address')
            ->selectRaw('COUNT(*) as cnt')
            ->where('created_at', '>=', $since24h)
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->having('cnt', '>', self::IP_THRESHOLD)
            ->each(function ($row) use (&$anomalies): void {
                $anomalies[] = "IP {$row->ip_address} : {$row->cnt} actions en 24 h";
            });

        // — Utilisateurs avec volume horaire excessif —
        AuditLog::query()
            ->select('actor_id')
            ->selectRaw('COUNT(*) as cnt')
            ->where('created_at', '>=', $since1h)
            ->whereNotNull('actor_id')
            ->groupBy('actor_id')
            ->having('cnt', '>', self::USER_THRESHOLD_HOURLY)
            ->with('actor:id,name')
            ->each(function ($row) use (&$anomalies): void {
                $name = $row->actor?->name ?? "ID {$row->actor_id}";
                $anomalies[] = "Utilisateur {$name} : {$row->cnt} actions en 1 h";
            });

        if (empty($anomalies)) {
            return;
        }

        // Évite les notifications en doublon sur la même heure
        $cacheKey = 'audit_anomaly_notified_' . $now->format('Y-m-d-H');
        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 3600);

        $body = implode("\n", array_map(fn ($a) => "• {$a}", $anomalies));

        // Notifie tous les Super Admin
        User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->each(function (User $admin) use ($body): void {
                Notification::make()
                    ->title('⚠ Anomalie d\'audit détectée')
                    ->body($body)
                    ->danger()
                    ->sendToDatabase($admin);
            });
    }
}
