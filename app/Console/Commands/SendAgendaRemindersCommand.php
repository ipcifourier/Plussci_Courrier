<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Meeting;
use App\Notifications\AppointmentReminderNotification;
use App\Notifications\MeetingReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * A1 — Envoie les rappels de rendez-vous et réunions à venir.
 * Tourné toutes les heures — envoie les rappels J-1 et H-2.
 */
class SendAgendaRemindersCommand extends Command
{
    protected $signature = 'agenda:send-reminders
                            {--dry-run : Affiche les rappels sans les envoyer}';

    protected $description = 'Envoie les rappels de rendez-vous et réunions (J-1 et H-2).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $now    = Carbon::now();

        $rdvCount = $this->processAppointments($now, $dryRun);
        $mtgCount = $this->processMeetings($now, $dryRun);

        if ($dryRun) {
            $this->line("<comment>[DRY-RUN]</comment> {$rdvCount} RDV et {$mtgCount} réunion(s) recevraient un rappel.");
        } else {
            $this->info("{$rdvCount} rappel(s) RDV et {$mtgCount} rappel(s) réunion envoyés.");
        }

        return self::SUCCESS;
    }

    private function processAppointments(Carbon $now, bool $dryRun): int
    {
        $sent = 0;

        // J-1 : rendez-vous entre demain 00:00 et 23:59
        $tomorrowStart = $now->copy()->addDay()->startOfDay();
        $tomorrowEnd   = $now->copy()->addDay()->endOfDay();

        // H-2 : rendez-vous dans les 2 heures
        $in2hStart = $now->copy()->addHours(2)->subMinutes(5);
        $in2hEnd   = $now->copy()->addHours(2)->addMinutes(5);

        $windows = [
            'J-1' => [$tomorrowStart, $tomorrowEnd],
            'H-2' => [$in2hStart, $in2hEnd],
        ];

        foreach ($windows as $window => [$start, $end]) {
            Appointment::query()
                ->whereBetween('starts_at', [$start, $end])
                ->whereIn('status', ['planned', 'confirmed', 'rescheduled'])
                ->with(['assignee', 'creator'])
                ->each(function (Appointment $appointment) use ($window, $dryRun, &$sent): void {
                    $recipients = collect([
                        $appointment->assignee,
                        $appointment->creator,
                    ])->filter()->unique('id');

                    if ($dryRun) {
                        $this->line("  [RDV {$window}] {$appointment->title} → " . $recipients->pluck('name')->implode(', '));
                        $sent++;
                        return;
                    }

                    foreach ($recipients as $user) {
                        $user->notify(new AppointmentReminderNotification($appointment, $window));
                    }

                    $sent++;
                });
        }

        return $sent;
    }

    private function processMeetings(Carbon $now, bool $dryRun): int
    {
        $sent = 0;

        $windows = [
            'J-1' => [$now->copy()->addDay()->startOfDay(), $now->copy()->addDay()->endOfDay()],
            'H-2' => [$now->copy()->addHours(2)->subMinutes(5), $now->copy()->addHours(2)->addMinutes(5)],
        ];

        foreach ($windows as $window => [$start, $end]) {
            Meeting::query()
                ->whereBetween('starts_at', [$start, $end])
                ->whereNotIn('status', ['cancelled'])
                ->with('participants')
                ->each(function (Meeting $meeting) use ($window, $dryRun, &$sent): void {
                    if ($dryRun) {
                        $this->line("  [Réunion {$window}] {$meeting->title} → {$meeting->participants->count()} participant(s)");
                        $sent++;
                        return;
                    }

                    foreach ($meeting->participants as $user) {
                        $user->notify(new MeetingReminderNotification($meeting, $window));
                    }

                    $sent++;
                });
        }

        return $sent;
    }
}
