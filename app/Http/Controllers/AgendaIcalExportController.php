<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Meeting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgendaIcalExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($this->canView($user), 403);

        $dateFrom = $request->filled('date_from')
            ? CarbonImmutable::parse($request->string('date_from')->toString())
            : CarbonImmutable::now()->subMonths(1);

        $dateTo = $request->filled('date_to')
            ? CarbonImmutable::parse($request->string('date_to')->toString())
            : CarbonImmutable::now()->addMonths(3);

        $appointments = Appointment::query()
            ->whereBetween('starts_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->get();

        $meetings = Meeting::query()
            ->whereBetween('starts_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->get();

        $prodId   = '-//PLUSS.CI//Agenda//FR';
        $fileName = 'agenda-' . now()->format('Ymd') . '.ics';

        return response()->streamDownload(function () use ($appointments, $meetings, $prodId): void {
            echo "BEGIN:VCALENDAR\r\n";
            echo "VERSION:2.0\r\n";
            echo "PRODID:{$prodId}\r\n";
            echo "CALSCALE:GREGORIAN\r\n";
            echo "METHOD:PUBLISH\r\n";

            foreach ($appointments as $a) {
                echo "BEGIN:VEVENT\r\n";
                echo 'UID:appointment-' . $a->id . "@pluss.ci\r\n";
                echo 'DTSTAMP:' . now()->format('Ymd\THis\Z') . "\r\n";
                echo 'DTSTART:' . optional($a->starts_at)?->format('Ymd\THis\Z') . "\r\n";
                echo 'DTEND:' . optional($a->ends_at)?->format('Ymd\THis\Z') . "\r\n";
                echo 'SUMMARY:' . $this->escapeIcal($a->title ?? '') . "\r\n";
                echo 'LOCATION:' . $this->escapeIcal($a->location ?? '') . "\r\n";
                echo 'CATEGORIES:Rendez-vous' . "\r\n";
                echo "END:VEVENT\r\n";
            }

            foreach ($meetings as $m) {
                echo "BEGIN:VEVENT\r\n";
                echo 'UID:meeting-' . $m->id . "@pluss.ci\r\n";
                echo 'DTSTAMP:' . now()->format('Ymd\THis\Z') . "\r\n";
                echo 'DTSTART:' . optional($m->starts_at)?->format('Ymd\THis\Z') . "\r\n";
                echo 'DTEND:' . optional($m->ends_at)?->format('Ymd\THis\Z') . "\r\n";
                echo 'SUMMARY:' . $this->escapeIcal($m->title ?? '') . "\r\n";
                echo 'LOCATION:' . $this->escapeIcal($m->location ?? '') . "\r\n";
                echo 'CATEGORIES:Réunion' . "\r\n";
                echo "END:VEVENT\r\n";
            }

            echo "END:VCALENDAR\r\n";
        }, $fileName, [
            'Content-Type'        => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function escapeIcal(string $value): string
    {
        return str_replace(['\\', ',', ';', "\n"], ['\\\\', '\\,', '\\;', '\\n'], $value);
    }

    private function canView(mixed $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $freshUser = User::query()->find($user->id);

        if (! $freshUser instanceof User) {
            return false;
        }

        if ($freshUser->hasRole('Super Admin')) {
            return true;
        }

        try {
            return $freshUser->hasPermissionTo('agenda.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
