<?php

namespace App\Console\Commands;

use App\Models\Courrier;
use App\Notifications\CourrierDeadlineAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * C2 — Envoie les alertes d'échéance courrier quotidiennement.
 * Configuré pour tourner chaque jour à 07:30.
 */
class SendCourrierDeadlineAlertsCommand extends Command
{
    protected $signature = 'courrier:send-deadline-alerts
                            {--days=3,1,0 : Jours avant échéance pour envoyer l\'alerte (séparés par virgule)}
                            {--dry-run    : Affiche sans envoyer}';

    protected $description = 'Envoie des alertes de rappel d\'échéance pour les courriers en retard ou proches de leur délai.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $days   = array_map('intval', explode(',', $this->option('days')));

        $sent  = 0;
        $today = Carbon::today();

        foreach ($days as $day) {
            $targetDate = $today->copy()->addDays($day);

            Courrier::query()
                ->whereDate('delai_reponse', $targetDate->toDateString())
                ->whereNotIn('statut', ['Traité', 'Archivé'])
                ->with('initiateur')
                ->each(function (Courrier $courrier) use ($day, $dryRun, &$sent): void {
                    $cacheKey = "courrier_deadline_alert_{$courrier->id}_{$day}";

                    if (Cache::has($cacheKey)) {
                        return;
                    }

                    $user = $courrier->initiateur;

                    if (! $user) {
                        return;
                    }

                    if ($dryRun) {
                        $this->line("  [J{$day}] {$courrier->reference} → {$user->name}");
                        $sent++;
                        return;
                    }

                    $user->notify(new CourrierDeadlineAlertNotification($courrier, $day));
                    Cache::put($cacheKey, true, now()->endOfDay());
                    $sent++;
                });
        }

        if ($dryRun) {
            $this->line("<comment>[DRY-RUN]</comment> {$sent} alerte(s) seraient envoyées.");
        } else {
            $this->info("{$sent} alerte(s) d'échéance envoyées.");
        }

        return self::SUCCESS;
    }
}
