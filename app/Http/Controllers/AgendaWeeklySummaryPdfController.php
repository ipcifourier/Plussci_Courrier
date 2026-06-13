<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Meeting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\Response;

class AgendaWeeklySummaryPdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($this->canView($user), 403);

        $weekStart = $request->filled('week_start')
            ? CarbonImmutable::parse($request->string('week_start')->toString())->startOfWeek()
            : CarbonImmutable::now()->startOfWeek();

        $weekEnd = $weekStart->endOfWeek();

        $appointments = Appointment::query()
            ->with(['creator', 'assignee'])
            ->whereBetween('starts_at', [$weekStart, $weekEnd])
            ->orderBy('starts_at')
            ->get();

        $meetings = Meeting::query()
            ->with(['facilitator', 'participants'])
            ->whereBetween('starts_at', [$weekStart, $weekEnd])
            ->orderBy('starts_at')
            ->get();

        $pdf = Pdf::loadView('pdf.agenda-weekly-summary', [
            'weekStart'    => $weekStart,
            'weekEnd'      => $weekEnd,
            'appointments' => $appointments,
            'meetings'     => $meetings,
            'generatedAt'  => now(),
            'generatedBy'  => $user,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('synthese-agenda-' . $weekStart->format('Y-W') . '.pdf');
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
