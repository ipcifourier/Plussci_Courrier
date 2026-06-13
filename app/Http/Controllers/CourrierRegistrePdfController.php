<?php

namespace App\Http\Controllers;

use App\Models\Courrier;
use App\Models\User;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CourrierRegistrePdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $freshUser = User::query()->find($user->id);

        abort_unless((bool) $freshUser?->hasPermissionTo('courriers.export'), 403);

        $query = Courrier::query()
            ->with(['correspondant', 'initiateur', 'signer'])
            ->orderByDesc('date_reception_envoi');

        $filters = [
            'type' => $request->string('type')->toString(),
            'statut' => $request->string('statut')->toString(),
            'date_debut' => $request->string('date_debut')->toString(),
            'date_fin' => $request->string('date_fin')->toString(),
            'signed_only' => $request->boolean('signed_only'),
        ];

        if ($filters['type']) {
            $query->where('type', $filters['type']);
        }

        if ($filters['statut']) {
            $query->where('statut', $filters['statut']);
        }

        if ($filters['date_debut']) {
            $query->whereDate('date_reception_envoi', '>=', $filters['date_debut']);
        }

        if ($filters['date_fin']) {
            $query->whereDate('date_reception_envoi', '<=', $filters['date_fin']);
        }

        if ($filters['signed_only']) {
            $query->whereNotNull('signed_at');
        }

        $courriers = $query->get();

        app(AuditLogger::class)->log(
            action: 'courriers.export.pdf',
            entity: null,
            meta: [
                'filters' => $filters,
                'count' => $courriers->count(),
            ],
        );

        $pdf = Pdf::loadView('pdf.courriers-registre', [
            'courriers' => $courriers,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('registre-courriers-' . now()->format('Ymd-His') . '.pdf');
    }
}
