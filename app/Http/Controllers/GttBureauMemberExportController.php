<?php

namespace App\Http\Controllers;

use App\Models\BureauMember;
use App\Models\Gtt;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GttBureauMemberExportController extends Controller
{
    public function __invoke(Request $request, Gtt $gtt): StreamedResponse
    {
        $user = $request->user() ?? Auth::user();
        abort_unless($this->canExport($user, $gtt), 403);

        $format   = strtolower((string) $request->query('format', 'xlsx'));
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 422);

        $fileName = 'gtt-' . $gtt->id . '-bureau-members-' . now()->format('Ymd-His') . ($format === 'xlsx' ? '.xlsx' : '.csv');

        $members = BureauMember::query()
            ->with('structure')
            ->where('gtt_id', $gtt->id)
            ->orderBy('nom')
            ->get();

        app(AuditLogger::class)->log(
            action: 'gtt.bureau_members.export',
            entity: $gtt,
            meta: ['gtt_id' => $gtt->id, 'format' => $format],
        );

        $writeRows = function ($writer) use ($members): void {
            $writer->addRow(new Row([
                Cell::fromValue('Nom'),
                Cell::fromValue('Prénom'),
                Cell::fromValue('Fonction'),
                Cell::fromValue('Email'),
                Cell::fromValue('Téléphone'),
                Cell::fromValue('Structure'),
                Cell::fromValue('Date d\'entrée'),
                Cell::fromValue('Statut'),
            ]));

            foreach ($members as $member) {
                $writer->addRow(new Row([
                    Cell::fromValue($member->nom ?? ''),
                    Cell::fromValue($member->prenom ?? ''),
                    Cell::fromValue($member->fonction ?? ''),
                    Cell::fromValue($member->email ?? ''),
                    Cell::fromValue($member->telephone ?? ''),
                    Cell::fromValue($member->structure?->nom ?? ''),
                    Cell::fromValue(optional($member->date_entree)?->format('Y-m-d') ?? ''),
                    Cell::fromValue($member->statut ? 'Actif' : 'Inactif'),
                ]));
            }
        };

        if ($format === 'xlsx') {
            return response()->streamDownload(function () use ($writeRows): void {
                $writer = new XlsxWriter();
                $writer->openToFile('php://output');
                $writeRows($writer);
                $writer->close();
            }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        }

        return response()->streamDownload(function () use ($writeRows): void {
            $writer = new CsvWriter();
            $writer->openToFile('php://output');
            $writeRows($writer);
            $writer->close();
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function canExport(mixed $user, Gtt $gtt): bool
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

        if ($freshUser->hasRole('GTT Responsable') && (int) $gtt->responsable === (int) $freshUser->id) {
            return true;
        }

        try {
            return $freshUser->hasPermissionTo('gtt.members.view');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
