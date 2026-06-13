<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\Response;

class AuditLogPdfExportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($this->canExport($user), 403);

        $filters = [
            'action'      => $request->string('action')->toString(),
            'actor_id'    => $request->string('actor_id')->toString(),
            'entity_type' => $request->string('entity_type')->toString(),
            'date_from'   => $request->string('date_from')->toString(),
            'date_to'     => $request->string('date_to')->toString(),
        ];

        $logs = AuditLog::query()
            ->with('actor')
            ->when(filled($filters['action']),      fn (Builder $q) => $q->where('action', $filters['action']))
            ->when(filled($filters['actor_id']),    fn (Builder $q) => $q->where('actor_id', (int) $filters['actor_id']))
            ->when(filled($filters['entity_type']), fn (Builder $q) => $q->where('entity_type', $filters['entity_type']))
            ->when(filled($filters['date_from']),   fn (Builder $q) => $q->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay()))
            ->when(filled($filters['date_to']),     fn (Builder $q) => $q->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay()))
            ->orderByDesc('created_at')
            ->limit(2000)
            ->get();

        app(AuditLogger::class)->log(
            action: 'audit.export.pdf',
            entity: null,
            meta: ['filters' => $filters, 'count' => $logs->count()],
        );

        $pdf = Pdf::loadView('pdf.audit-logs', [
            'logs'        => $logs,
            'filters'     => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('audit-logs-' . now()->format('Ymd-His') . '.pdf');
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
            return $freshUser->hasPermissionTo('audit.export');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
