<?php

namespace App\Http\Controllers;

use App\Models\Courrier;
use App\Models\User;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\Response;

class CourrierSignatureSheetController extends Controller
{
    public function __invoke(Request $request, Courrier $courrier): Response
    {
        $user = $request->user();
        abort_unless($this->canView($user, $courrier), 403);

        $courrier->load(['correspondant', 'initiateur', 'signer', 'approvals.approver', 'imputations.destinataire']);

        app(AuditLogger::class)->log(
            action: 'courriers.signature.pdf',
            entity: $courrier,
            meta: ['courrier_id' => $courrier->id, 'reference' => $courrier->reference],
        );

        $pdf = Pdf::loadView('pdf.courrier-signature-sheet', [
            'courrier'    => $courrier,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('feuille-signature-' . ($courrier->reference ?? $courrier->id) . '.pdf');
    }

    private function canView(mixed $user, Courrier $courrier): bool
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
            return $freshUser->hasPermissionTo('courriers.view') || $freshUser->hasPermissionTo('courriers.sign');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
