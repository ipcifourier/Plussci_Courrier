<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Meeting;
use App\Models\User;
use App\Models\Visit;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * A2 — Page calendrier mensuel/hebdomadaire pour l'Agenda.
 */
class AgendaCalendarPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $slug = 'agenda-calendrier';

    protected static ?string $navigationLabel = 'Calendrier';

    protected static ?string $title = 'Calendrier';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.agenda-calendar';

    public static function getNavigationGroup(): ?string
    {
        return 'Agenda';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasAnyPermission(['agenda.view', 'agenda.viewAny', 'agenda.appointments.manage', 'agenda.meetings.manage'])
        );
    }

    /** Retourne les événements JSON pour FullCalendar. */
    public function getEvents(): string
    {
        $user   = Auth::user();
        $events = [];

        // Rendez-vous
        Appointment::query()
            ->whereIn('status', ['planned', 'confirmed', 'effective', 'rescheduled'])
            ->where(function ($q) use ($user): void {
                if ($user instanceof User && ! $user->hasRole('Super Admin')) {
                    $q->where('assigned_to', $user->id)->orWhere('created_by', $user->id);
                }
            })
            ->with(['assignee:id,name'])
            ->limit(500)
            ->get()
            ->each(function (Appointment $apt) use (&$events): void {
                $events[] = [
                    'id'    => 'apt-' . $apt->id,
                    'title' => $apt->title,
                    'start' => $apt->starts_at->toIso8601String(),
                    'end'   => $apt->ends_at?->toIso8601String(),
                    'color' => match ($apt->status) {
                        'confirmed' => '#059669',
                        'effective' => '#2563eb',
                        'cancelled' => '#dc2626',
                        default     => '#d97706',
                    },
                    'extendedProps' => [
                        'type'     => 'rdv',
                        'assignee' => $apt->assignee?->name,
                        'location' => $apt->location,
                        'status'   => $apt->status,
                    ],
                ];
            });

        // Réunions
        Meeting::query()
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($q): void {
                // Exclure les réunions du planning sans date précise saisie
                // (créées avec starts_at = now() comme placeholder)
                $q->whereNull('planning_year')
                  ->orWhereNotNull('planned_date');
            })
            ->where(function ($q) use ($user): void {
                if ($user instanceof User && ! $user->hasRole('Super Admin')) {
                    $q->where('facilitator_id', $user->id)
                      ->orWhereHas('participants', fn ($sub) => $sub->where('users.id', $user->id));
                }
            })
            ->limit(500)
            ->get()
            ->each(function (Meeting $meeting) use (&$events): void {
                // Utiliser planned_date si disponible, sinon starts_at
                $startDate = $meeting->planned_date
                    ? $meeting->planned_date->toDateString()
                    : $meeting->starts_at->toIso8601String();
                $events[] = [
                    'id'    => 'mtg-' . $meeting->id,
                    'title' => '📋 ' . $meeting->title,
                    'start' => $startDate,
                    'end'   => $meeting->planned_date ? null : $meeting->ends_at?->toIso8601String(),
                    'color' => '#7c3aed',
                    'extendedProps' => [
                        'type'     => 'reunion',
                        'location' => $meeting->location,
                        'status'   => $meeting->status,
                    ],
                ];
            });

        // Visites
        Visit::query()
            ->where(function ($q) use ($user): void {
                if ($user instanceof User && ! $user->hasRole('Super Admin')) {
                    $q->where('created_by', $user->id);
                }
            })
            ->limit(200)
            ->get()
            ->each(function (Visit $visit) use (&$events): void {
                $title = trim(($visit->visitor_first_name ?? '') . ' ' . ($visit->visitor_last_name ?? '')) ?: 'Visite';
                $events[] = [
                    'id'    => 'vst-' . $visit->id,
                    'title' => '👤 ' . $title,
                    'start' => ($visit->happened_at ?? $visit->created_at)?->toIso8601String() ?? now()->toIso8601String(),
                    'color' => '#0891b2',
                    'extendedProps' => ['type' => 'visite'],
                ];
            });

        return json_encode($events, JSON_UNESCAPED_UNICODE);
    }
}
