<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogExportController
{
    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user() ?? Auth::user();

        abort_unless($this->canExport($user), 403);

        $format = strtolower((string) $request->query('format', 'csv'));
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 422);

        $fileName = 'audit-logs-' . now()->format('Ymd-His') . ($format === 'xlsx' ? '.xlsx' : '.csv');

        $query = $this->buildQuery($request);

        if ($format === 'xlsx') {
            return response()->streamDownload(function () use ($query): void {
                $writer = new XlsxWriter();
                $writer->openToFile('php://output');

                $writer->addRow($this->headerRow());

                $query->chunkById(500, function ($logs) use ($writer): void {
                    foreach ($logs as $log) {
                        $writer->addRow($this->toRow($log));
                    }
                });

                $writer->close();
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        return response()->streamDownload(function () use ($query): void {
            $writer = new CsvWriter();
            $writer->openToFile('php://output');

            $writer->addRow($this->headerRow());

            $query->chunkById(500, function ($logs) use ($writer): void {
                foreach ($logs as $log) {
                    $writer->addRow($this->toRow($log));
                }
            });

            $writer->close();
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function buildQuery(Request $request): Builder
    {
        $filters = $this->extractFilters($request);

        return AuditLog::query()
            ->with('actor')
            ->when(filled($filters['action'] ?? null), fn (Builder $query) => $query->where('action', $filters['action']))
            ->when(filled($filters['actor_id'] ?? null), fn (Builder $query) => $query->where('actor_id', (int) $filters['actor_id']))
            ->when(filled($filters['entity_type'] ?? null), fn (Builder $query) => $query->where('entity_type', $filters['entity_type']))
            ->when(filled($filters['date_from'] ?? null), function (Builder $query) use ($filters): void {
                $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
            })
            ->when(filled($filters['date_to'] ?? null), function (Builder $query) use ($filters): void {
                $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
            })
            ->orderByDesc('created_at');
    }

    protected function extractFilters(Request $request): array
    {
        $tableFilters = $request->query('tableFilters', []);

        $dateRange = is_array($tableFilters['date_range'] ?? null) ? $tableFilters['date_range'] : [];
        $actionFilter = is_array($tableFilters['action'] ?? null) ? $tableFilters['action'] : [];
        $actorFilter = is_array($tableFilters['actor_id'] ?? null) ? $tableFilters['actor_id'] : [];
        $entityTypeFilter = is_array($tableFilters['entity_type'] ?? null) ? $tableFilters['entity_type'] : [];

        return [
            'action' => $request->query('action') ?? ($actionFilter['value'] ?? null),
            'actor_id' => $request->query('actor_id') ?? ($actorFilter['value'] ?? null),
            'entity_type' => $request->query('entity_type') ?? ($entityTypeFilter['value'] ?? null),
            'date_from' => $request->query('date_from') ?? ($dateRange['date_from'] ?? null),
            'date_to' => $request->query('date_to') ?? ($dateRange['date_to'] ?? null),
        ];
    }

    protected function headerRow(): Row
    {
        return new Row([
            Cell::fromValue('Date'),
            Cell::fromValue('Acteur'),
            Cell::fromValue('Action'),
            Cell::fromValue('Entité'),
            Cell::fromValue('ID entité'),
            Cell::fromValue('IP'),
            Cell::fromValue('Avant (JSON)'),
            Cell::fromValue('Après (JSON)'),
            Cell::fromValue('Métadonnées (JSON)'),
        ]);
    }

    protected function toRow(AuditLog $log): Row
    {
        return new Row([
            Cell::fromValue(optional($log->created_at)?->format('Y-m-d H:i:s')),
            Cell::fromValue($log->actor?->name ?? 'Système'),
            Cell::fromValue($log->action),
            Cell::fromValue($log->entity_type ?? ''),
            Cell::fromValue((string) ($log->entity_id ?? '')),
            Cell::fromValue($log->ip_address ?? ''),
            Cell::fromValue($log->before_json ? json_encode($log->before_json, JSON_UNESCAPED_UNICODE) : ''),
            Cell::fromValue($log->after_json ? json_encode($log->after_json, JSON_UNESCAPED_UNICODE) : ''),
            Cell::fromValue($log->meta_json ? json_encode($log->meta_json, JSON_UNESCAPED_UNICODE) : ''),
        ]);
    }

    protected function canExport(mixed $user): bool
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
