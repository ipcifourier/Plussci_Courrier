<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Meeting;
use App\Models\MeetingTask;
use App\Models\User;
use App\Models\Visit;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgendaExportController extends Controller
{
    private const ALLOWED = ['appointments', 'meetings', 'meeting-tasks', 'visits'];

    public function __invoke(Request $request, string $resource): StreamedResponse
    {
        abort_unless(in_array($resource, self::ALLOWED, true), 404);

        $user = $request->user() ?? Auth::user();
        abort_unless($this->canExport($user), 403);

        $format   = strtolower((string) $request->query('format', 'xlsx'));
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 422);

        $fileName = $resource . '-' . now()->format('Ymd-His') . ($format === 'xlsx' ? '.xlsx' : '.csv');

        [$query, $headerRow, $rowFn] = match ($resource) {
            'appointments' => [$this->appointmentsQuery($request), $this->appointmentsHeader(), fn ($i) => $this->appointmentRow($i)],
            'meetings'     => [$this->meetingsQuery($request), $this->meetingsHeader(), fn ($i) => $this->meetingRow($i)],
            'meeting-tasks'=> [$this->meetingTasksQuery($request), $this->meetingTasksHeader(), fn ($i) => $this->meetingTaskRow($i)],
            'visits'       => [$this->visitsQuery($request), $this->visitsHeader(), fn ($i) => $this->visitRow($i)],
        };

        app(AuditLogger::class)->log(
            action: 'agenda.' . $resource . '.export',
            entity: null,
            meta: ['format' => $format],
        );

        if ($format === 'xlsx') {
            return response()->streamDownload(function () use ($query, $headerRow, $rowFn): void {
                $writer = new XlsxWriter();
                $writer->openToFile('php://output');
                $writer->addRow($headerRow);
                $query->chunkById(500, fn ($items) => collect($items)->each(fn ($i) => $writer->addRow($rowFn($i))));
                $writer->close();
            }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        }

        return response()->streamDownload(function () use ($query, $headerRow, $rowFn): void {
            $writer = new CsvWriter();
            $writer->openToFile('php://output');
            $writer->addRow($headerRow);
            $query->chunkById(500, fn ($items) => collect($items)->each(fn ($i) => $writer->addRow($rowFn($i))));
            $writer->close();
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ── Appointments ──────────────────────────────────────────────────────────

    private function appointmentsQuery(Request $request): Builder
    {
        return Appointment::query()->with(['creator', 'assignee'])
            ->when(filled($request->query('status')), fn (Builder $q) => $q->where('status', $request->query('status')))
            ->when(filled($request->query('date_from')), fn (Builder $q) => $q->whereDate('starts_at', '>=', Carbon::parse($request->query('date_from'))))
            ->when(filled($request->query('date_to')), fn (Builder $q) => $q->whereDate('starts_at', '<=', Carbon::parse($request->query('date_to'))))
            ->orderBy('starts_at');
    }

    private function appointmentsHeader(): Row
    {
        return new Row([
            Cell::fromValue('Titre'), Cell::fromValue('Type'), Cell::fromValue('Statut'),
            Cell::fromValue('Début'), Cell::fromValue('Fin'), Cell::fromValue('Lieu'),
            Cell::fromValue('Créé par'), Cell::fromValue('Assigné à'),
        ]);
    }

    private function appointmentRow(Appointment $a): Row
    {
        return new Row([
            Cell::fromValue($a->title ?? ''),
            Cell::fromValue($a->type ?? ''),
            Cell::fromValue($a->status ?? ''),
            Cell::fromValue(optional($a->starts_at)?->format('Y-m-d H:i') ?? ''),
            Cell::fromValue(optional($a->ends_at)?->format('Y-m-d H:i') ?? ''),
            Cell::fromValue($a->location ?? ''),
            Cell::fromValue($a->creator?->name ?? ''),
            Cell::fromValue($a->assignee?->name ?? ''),
        ]);
    }

    // ── Meetings ──────────────────────────────────────────────────────────────

    private function meetingsQuery(Request $request): Builder
    {
        return Meeting::query()->with(['facilitator'])
            ->when(filled($request->query('status')), fn (Builder $q) => $q->where('status', $request->query('status')))
            ->when(filled($request->query('date_from')), fn (Builder $q) => $q->whereDate('starts_at', '>=', Carbon::parse($request->query('date_from'))))
            ->when(filled($request->query('date_to')), fn (Builder $q) => $q->whereDate('starts_at', '<=', Carbon::parse($request->query('date_to'))))
            ->orderBy('starts_at');
    }

    private function meetingsHeader(): Row
    {
        return new Row([
            Cell::fromValue('Titre'), Cell::fromValue('Statut'), Cell::fromValue('Début'),
            Cell::fromValue('Fin'), Cell::fromValue('Lieu'), Cell::fromValue('Facilitateur'),
        ]);
    }

    private function meetingRow(Meeting $m): Row
    {
        return new Row([
            Cell::fromValue($m->title ?? ''),
            Cell::fromValue($m->status ?? ''),
            Cell::fromValue(optional($m->starts_at)?->format('Y-m-d H:i') ?? ''),
            Cell::fromValue(optional($m->ends_at)?->format('Y-m-d H:i') ?? ''),
            Cell::fromValue($m->location ?? ''),
            Cell::fromValue($m->facilitator?->name ?? ''),
        ]);
    }

    // ── Meeting Tasks ─────────────────────────────────────────────────────────

    private function meetingTasksQuery(Request $request): Builder
    {
        return MeetingTask::query()->with(['meeting'])
            ->when(filled($request->query('status')), fn (Builder $q) => $q->where('status', $request->query('status')))
            ->orderBy('due_at');
    }

    private function meetingTasksHeader(): Row
    {
        return new Row([
            Cell::fromValue('Titre'), Cell::fromValue('Réunion'), Cell::fromValue('Statut'),
            Cell::fromValue('Priorité'), Cell::fromValue('Échéance'), Cell::fromValue('Terminée le'),
        ]);
    }

    private function meetingTaskRow(MeetingTask $t): Row
    {
        return new Row([
            Cell::fromValue($t->title ?? ''),
            Cell::fromValue($t->meeting?->title ?? ''),
            Cell::fromValue($t->status ?? ''),
            Cell::fromValue($t->priority ?? ''),
            Cell::fromValue(optional($t->due_at)?->format('Y-m-d') ?? ''),
            Cell::fromValue(optional($t->completed_at)?->format('Y-m-d H:i') ?? ''),
        ]);
    }

    // ── Visits ────────────────────────────────────────────────────────────────

    private function visitsQuery(Request $request): Builder
    {
        return Visit::query()
            ->when(filled($request->query('date_from')), fn (Builder $q) => $q->whereDate('visited_at', '>=', Carbon::parse($request->query('date_from'))))
            ->when(filled($request->query('date_to')), fn (Builder $q) => $q->whereDate('visited_at', '<=', Carbon::parse($request->query('date_to'))))
            ->orderByDesc('visited_at');
    }

    private function visitsHeader(): Row
    {
        return new Row([
            Cell::fromValue('Visiteur'), Cell::fromValue('Objet'), Cell::fromValue('Date'),
            Cell::fromValue('Statut'), Cell::fromValue('Contact'),
        ]);
    }

    private function visitRow(Visit $v): Row
    {
        return new Row([
            Cell::fromValue($v->visitor_name ?? ''),
            Cell::fromValue($v->purpose ?? ''),
            Cell::fromValue(optional($v->visited_at)?->format('Y-m-d H:i') ?? ''),
            Cell::fromValue($v->status ?? ''),
            Cell::fromValue($v->contact_phone ?? ''),
        ]);
    }

    private function canExport(mixed $user): bool
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
