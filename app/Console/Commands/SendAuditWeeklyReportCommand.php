<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * AU3 — Envoie un rapport hebdomadaire d'audit par e-mail aux Super Admin.
 * Planifié tous les lundis à 08:00.
 */
class SendAuditWeeklyReportCommand extends Command
{
    protected $signature = 'audit:send-weekly-report
                            {--dry-run : Affiche le rapport sans envoyer}';

    protected $description = 'Envoie le rapport hebdomadaire d\'audit aux Super Admin.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $since  = Carbon::now()->subWeek()->startOfDay();
        $until  = Carbon::now()->endOfDay();

        // — Statistiques de la semaine —
        $totalActions = AuditLog::query()
            ->whereBetween('created_at', [$since, $until])
            ->count();

        $actionBreakdown = AuditLog::query()
            ->select('action')
            ->selectRaw('COUNT(*) as cnt')
            ->whereBetween('created_at', [$since, $until])
            ->groupBy('action')
            ->orderByDesc('cnt')
            ->limit(10)
            ->pluck('cnt', 'action')
            ->all();

        $topActors = AuditLog::query()
            ->select('actor_id')
            ->selectRaw('COUNT(*) as cnt')
            ->whereBetween('created_at', [$since, $until])
            ->whereNotNull('actor_id')
            ->groupBy('actor_id')
            ->orderByDesc('cnt')
            ->limit(5)
            ->with('actor:id,name')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->actor?->name ?? "ID {$row->actor_id}",
                'cnt'  => $row->cnt,
            ])
            ->all();

        $anomalousIps = AuditLog::query()
            ->select('ip_address')
            ->selectRaw('COUNT(*) as cnt')
            ->whereBetween('created_at', [$since, $until])
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->having('cnt', '>', 100)
            ->pluck('cnt', 'ip_address')
            ->all();

        $period = $since->format('d/m/Y') . ' – ' . $until->format('d/m/Y');

        if ($dryRun) {
            $this->info("Rapport d'audit — {$period}");
            $this->line("Total actions : {$totalActions}");
            foreach ($actionBreakdown as $action => $cnt) {
                $this->line("  {$action} : {$cnt}");
            }
            $this->line('Acteurs principaux :');
            foreach ($topActors as $actor) {
                $this->line("  {$actor['name']} : {$actor['cnt']}");
            }
            if ($anomalousIps) {
                $this->warn('IPs suspectes : ' . implode(', ', array_keys($anomalousIps)));
            }
            return self::SUCCESS;
        }

        $admins = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->whereNotNull('email')
            ->get();

        foreach ($admins as $admin) {
            Mail::send([], [], function ($mail) use ($admin, $period, $totalActions, $actionBreakdown, $topActors, $anomalousIps): void {
                $breakdownHtml = '';
                foreach ($actionBreakdown as $action => $cnt) {
                    $breakdownHtml .= "<tr><td style='padding:4px 8px;border:1px solid #e5e7eb;'>" . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . "</td><td style='padding:4px 8px;border:1px solid #e5e7eb;text-align:right;'>{$cnt}</td></tr>";
                }

                $actorsHtml = '';
                foreach ($topActors as $actor) {
                    $actorsHtml .= "<tr><td style='padding:4px 8px;border:1px solid #e5e7eb;'>" . htmlspecialchars($actor['name'], ENT_QUOTES, 'UTF-8') . "</td><td style='padding:4px 8px;border:1px solid #e5e7eb;text-align:right;'>{$actor['cnt']}</td></tr>";
                }

                $anomalyHtml = empty($anomalousIps)
                    ? '<p style="color:green;">Aucune anomalie IP détectée cette semaine.</p>'
                    : '<p style="color:red;font-weight:bold;">⚠ IPs suspectes (&gt;100 actions) :</p><ul>' . implode('', array_map(fn ($ip, $cnt) => "<li>{$ip} : {$cnt} actions</li>", array_keys($anomalousIps), $anomalousIps)) . '</ul>';

                $html = "
                    <h2 style='color:#d97706;'>Rapport hebdomadaire d'audit — {$period}</h2>
                    <p><strong>Total actions :</strong> {$totalActions}</p>
                    <h3>Top 10 actions</h3>
                    <table style='border-collapse:collapse;width:100%;'><thead><tr>
                        <th style='padding:4px 8px;border:1px solid #e5e7eb;text-align:left;background:#f9fafb;'>Action</th>
                        <th style='padding:4px 8px;border:1px solid #e5e7eb;text-align:right;background:#f9fafb;'>Nb</th>
                    </tr></thead><tbody>{$breakdownHtml}</tbody></table>
                    <h3>Top 5 acteurs</h3>
                    <table style='border-collapse:collapse;width:100%;'><thead><tr>
                        <th style='padding:4px 8px;border:1px solid #e5e7eb;text-align:left;background:#f9fafb;'>Utilisateur</th>
                        <th style='padding:4px 8px;border:1px solid #e5e7eb;text-align:right;background:#f9fafb;'>Nb</th>
                    </tr></thead><tbody>{$actorsHtml}</tbody></table>
                    {$anomalyHtml}
                    <hr/>
                    <p style='color:#6b7280;font-size:12px;'>Généré automatiquement par PLUSS.CI — Ne pas répondre.</p>
                ";

                $mail->to($admin->email)
                    ->subject("Rapport audit PLUSS.CI — {$period}")
                    ->html($html);
            });
        }

        $this->info("Rapport envoyé à {$admins->count()} Super Admin.");

        return self::SUCCESS;
    }
}
