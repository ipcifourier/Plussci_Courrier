<?php

namespace App\Http\Controllers;

use App\Models\Courrier;
use App\Models\User;
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

class CourrierExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user() ?? Auth::user();

        abort_unless($this->canExport($user), 403);

        $format   = strtolower((string) $request->query('format', 'xlsx'));
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 422);

        $fileName = 'courriers-' . now()->format('Ymd-His') . ($format === 'xlsx' ? '.xlsx' : '.csv');

        $query = $this->buildQuery($request);

        app(AuditLogger::class)->log(
            action: 'courriers.export',
            entity: null,
            meta: ['format' => $format, 'filters' => $request->only(['type', 'statut', 'date_debut', 'date_fin'])],
        );

        if ($format === 'xlsx') {
            return response()->streamDownload(function () use ($query): void {
                $writer = new XlsxWriter();
                $writer->openToFile('php://output');
                $writer->addRow($this->headerRow());
                $query->chunkById(500, function ($courriers) use ($writer): void {
                    foreach ($courriers as $courrier) {
                        $writer->addRow($this->toRow($courrier));
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
            $query->chunkById(500, function ($courriers) use ($writer): void {
                foreach ($courriers as $courrier) {
                    $writer->addRow($this->toRow($courrier));
                }
            });
            $writer->close();
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildQuery(Request $request): Builder
    {
        return Courrier::query()
            ->with(['correspondant', 'initiateur'])
            ->when(filled($request->query('type')), fn (Builder $q) => $q->where('type', $request->query('type')))
            ->when(filled($request->query('statut')), fn (Builder $q) => $q->where('statut', $request->query('statut')))
            ->when(filled($request->query('date_debut')), fn (Builder $q) => $q->whereDate('date_reception_envoi', '>=', Carbon::parse($request->query('date_debut'))))
            ->when(filled($request->query('date_fin')), fn (Builder $q) => $q->whereDate('date_reception_envoi', '<=', Carbon::parse($request->query('date_fin'))))
            ->orderByDesc('date_reception_envoi');
    }

    private function headerRow(): Row
    {
        return new Row([
            Cell::fromValue('Référence'),
            Cell::fromValue('Type'),
            Cell::fromValue('Objet'),
            Cell::fromValue('Correspondant'),
            Cell::fromValue('Date'),
            Cell::fromValue('Statut'),
            Cell::fromValue('Priorité'),
            Cell::fromValue('Niveau confidentialité'),
            Cell::fromValue('Signé par'),
            Cell::fromValue('Date signature'),
        ]);
    }

    private function toRow(Courrier $courrier): Row
    {
        return new Row([
            Cell::fromValue($courrier->reference ?? ''),
            Cell::fromValue($courrier->type ?? ''),
            Cell::fromValue($courrier->objet ?? ''),
            Cell::fromValue($courrier->correspondant?->nom_structure ?? ''),
            Cell::fromValue(optional($courrier->date_reception_envoi)?->format('Y-m-d') ?? ''),
            Cell::fromValue($courrier->statut ?? ''),
            Cell::fromValue($courrier->priorite ?? ''),
            Cell::fromValue($courrier->niveau_confidentialite ?? ''),
            Cell::fromValue($courrier->signer?->name ?? ''),
            Cell::fromValue(optional($courrier->signed_at)?->format('Y-m-d H:i') ?? ''),
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
            return $freshUser->hasPermissionTo('courriers.export');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
