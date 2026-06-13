<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Dossier;
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

class GedExportController extends Controller
{
    public function __invoke(Request $request, string $resource): StreamedResponse
    {
        abort_unless(in_array($resource, ['documents', 'dossiers'], true), 404);

        $user = $request->user() ?? Auth::user();
        abort_unless($this->canExport($user), 403);

        $format   = strtolower((string) $request->query('format', 'xlsx'));
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 422);

        $fileName = $resource . '-' . now()->format('Ymd-His') . ($format === 'xlsx' ? '.xlsx' : '.csv');

        $query   = $resource === 'documents' ? $this->buildDocumentsQuery($request) : $this->buildDossiersQuery($request);
        $headers = $resource === 'documents' ? $this->documentHeaderRow() : $this->dossierHeaderRow();
        $rowFn   = $resource === 'documents'
            ? fn ($item) => $this->documentToRow($item)
            : fn ($item) => $this->dossierToRow($item);

        app(AuditLogger::class)->log(
            action: 'ged.' . $resource . '.export',
            entity: null,
            meta: ['format' => $format],
        );

        if ($format === 'xlsx') {
            return response()->streamDownload(function () use ($query, $headers, $rowFn): void {
                $writer = new XlsxWriter();
                $writer->openToFile('php://output');
                $writer->addRow($headers);
                $query->chunkById(500, function ($items) use ($writer, $rowFn): void {
                    foreach ($items as $item) {
                        $writer->addRow($rowFn($item));
                    }
                });
                $writer->close();
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        return response()->streamDownload(function () use ($query, $headers, $rowFn): void {
            $writer = new CsvWriter();
            $writer->openToFile('php://output');
            $writer->addRow($headers);
            $query->chunkById(500, function ($items) use ($writer, $rowFn): void {
                foreach ($items as $item) {
                    $writer->addRow($rowFn($item));
                }
            });
            $writer->close();
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildDocumentsQuery(Request $request): Builder
    {
        return Document::query()
            ->with(['auteur', 'dossier'])
            ->when(filled($request->query('etat_cycle_vie')), fn (Builder $q) => $q->where('etat_cycle_vie', $request->query('etat_cycle_vie')))
            ->when(filled($request->query('type_document')), fn (Builder $q) => $q->where('type_document', $request->query('type_document')))
            ->when(filled($request->query('date_from')), fn (Builder $q) => $q->whereDate('created_at', '>=', Carbon::parse($request->query('date_from'))))
            ->when(filled($request->query('date_to')), fn (Builder $q) => $q->whereDate('created_at', '<=', Carbon::parse($request->query('date_to'))))
            ->orderByDesc('created_at');
    }

    private function buildDossiersQuery(Request $request): Builder
    {
        return Dossier::query()
            ->with(['owner'])
            ->when(filled($request->query('statut')), fn (Builder $q) => $q->where('statut', $request->query('statut')))
            ->orderBy('libelle');
    }

    private function documentHeaderRow(): Row
    {
        return new Row([
            Cell::fromValue('Référence'),
            Cell::fromValue('Titre'),
            Cell::fromValue('Type document'),
            Cell::fromValue('Dossier'),
            Cell::fromValue('État'),
            Cell::fromValue('Auteur'),
            Cell::fromValue('Confidentialité'),
            Cell::fromValue('Date création'),
        ]);
    }

    private function dossierHeaderRow(): Row
    {
        return new Row([
            Cell::fromValue('Code'),
            Cell::fromValue('Libellé'),
            Cell::fromValue('Description'),
            Cell::fromValue('Confidentialité'),
            Cell::fromValue('Statut'),
            Cell::fromValue('Propriétaire'),
            Cell::fromValue('Date création'),
        ]);
    }

    private function documentToRow(Document $document): Row
    {
        return new Row([
            Cell::fromValue($document->reference_doc ?? ''),
            Cell::fromValue($document->titre ?? ''),
            Cell::fromValue($document->type_document ?? ''),
            Cell::fromValue($document->dossier?->libelle ?? ''),
            Cell::fromValue($document->etat_cycle_vie ?? ''),
            Cell::fromValue($document->auteur?->name ?? ''),
            Cell::fromValue($document->confidentiality_level ?? ''),
            Cell::fromValue(optional($document->created_at)?->format('Y-m-d H:i') ?? ''),
        ]);
    }

    private function dossierToRow(Dossier $dossier): Row
    {
        return new Row([
            Cell::fromValue($dossier->code ?? ''),
            Cell::fromValue($dossier->libelle ?? ''),
            Cell::fromValue($dossier->description ?? ''),
            Cell::fromValue($dossier->confidentialite ?? ''),
            Cell::fromValue($dossier->statut ?? ''),
            Cell::fromValue($dossier->owner?->name ?? ''),
            Cell::fromValue(optional($dossier->created_at)?->format('Y-m-d H:i') ?? ''),
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
            return $freshUser->hasPermissionTo('ged.documents.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
