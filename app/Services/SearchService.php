<?php

namespace App\Services;

use App\Models\Courrier;
use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SearchService
{
    // -------------------------------------------------------------------------
    // Documents
    // -------------------------------------------------------------------------

    /**
     * Search documents with full-text (titre, keywords) via MATCH..AGAINST
     * + extended OCR search + metadata filters.
     */
    public function searchDocuments(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $q = trim($filters['q'] ?? '');

        $query = Document::query()
            ->with(['dossier', 'auteur', 'currentVersion'])
            ->leftJoin(
                'document_versions',
                'documents.version_courante_id',
                '=',
                'document_versions.id'
            );

        // â”€â”€ Full-text search â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ($q !== '') {
            $isMysql = DB::getDriverName() === 'mysql';

            $query->where(function ($sub) use ($q, $isMysql): void {
                if ($isMysql) {
                    $sub->whereRaw(
                        'MATCH(documents.titre, documents.keywords) AGAINST (? IN BOOLEAN MODE)',
                        [$q . '*']
                    );
                } else {
                    $sub->where('documents.titre', 'like', "%{$q}%")
                        ->orWhere('documents.keywords', 'like', "%{$q}%");
                }
                $sub->orWhere('documents.reference_doc', 'like', "%{$q}%")
                    ->orWhere('document_versions.ocr_text', 'like', "%{$q}%")
                    ->orWhereJsonContains('documents.tags_json', $q);
            });

        }

        // â”€â”€ Filters â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if (! empty($filters['type_document'])) {
            $query->where('documents.type_document', $filters['type_document']);
        }

        if (! empty($filters['dossier_id'])) {
            $query->where('documents.dossier_id', $filters['dossier_id']);
        }

        if (! empty($filters['intervention_domain_id'])) {
            $query->where('documents.intervention_domain_id', $filters['intervention_domain_id']);
        }

        if (! empty($filters['intervention_subdomain_id'])) {
            $query->where('documents.intervention_subdomain_id', $filters['intervention_subdomain_id']);
        }

        if (! empty($filters['gtt_id'])) {
            $query->where('documents.gtt_id', $filters['gtt_id']);
        }

        if (! empty($filters['annee'])) {
            $query->whereYear('documents.created_at', (int) $filters['annee']);
        }

        if (! empty($filters['etat_cycle_vie'])) {
            $query->where('documents.etat_cycle_vie', $filters['etat_cycle_vie']);
        }

        if (! empty($filters['confidentiality_level'])) {
            $query->where('documents.confidentiality_level', $filters['confidentiality_level']);
        }

        if (! empty($filters['auteur_id'])) {
            $query->where('documents.auteur_id', $filters['auteur_id']);
        }

        if (! empty($filters['source'])) {
            $query->where('document_versions.source', $filters['source']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('documents.created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('documents.created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['tag'])) {
            $query->whereJsonContains('documents.tags_json', $filters['tag']);
        }

        // ── Order: fulltext relevance > recency ─────────────────────────────
        if ($q !== '' && DB::getDriverName() === 'mysql') {
            // Use the MATCH expression directly in ORDER BY to avoid a broken
            // column alias (selectRaw alias gets wiped by the final ->select()).
            $query->orderByRaw(
                'MATCH(documents.titre, documents.keywords) AGAINST (? IN BOOLEAN MODE) DESC',
                [$q . '*']
            );
        }
        $query->orderByDesc('documents.updated_at');

        return $query->select('documents.*')->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Courriers
    // -------------------------------------------------------------------------

    /**
     * Search courriers with full-text (rÃ©fÃ©rence, objet, rÃ©sumÃ©) + filters.
     */
    public function searchCourriers(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Courrier::query()->with(['correspondant', 'initiateur']);

        // â”€â”€ Full-text â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if ($q = trim($filters['q'] ?? '')) {
            $query->where(function ($sub) use ($q): void {
                $sub->where('reference', 'like', "%{$q}%")
                    ->orWhere('objet', 'like', "%{$q}%")
                    ->orWhere('resume', 'like', "%{$q}%");
            });
        }

        // â”€â”€ Filters â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (! empty($filters['priorite'])) {
            $query->where('priorite', $filters['priorite']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date_reception_envoi', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date_reception_envoi', '<=', $filters['date_to']);
        }

        if (! empty($filters['annee'])) {
            $query->whereYear('date_reception_envoi', (int) $filters['annee']);
        }

        return $query
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Highlight a keyword inside a text snippet (returns HTML).
     *
     * @param  string  $text   Raw text (will be html-escaped)
     * @param  string  $query  Search term
     * @param  int     $around Characters of context on each side
     */
    public function highlight(string $text, string $query, int $around = 80): string
    {
        $plain = strip_tags($text);

        if (! $query) {
            return e(mb_substr($plain, 0, $around * 2)) . '…';
        }

        $pos = mb_stripos($plain, $query);
        if ($pos === false) {
            return e(mb_substr($plain, 0, $around * 2)) . '…';
        }

        $start   = max(0, $pos - $around);
        $excerpt = mb_substr($plain, $start, $around * 2 + mb_strlen($query));
        $prefix  = $start > 0 ? '…' : '';

        // Escape then re-insert bold tag
        $escaped = e($excerpt);
        $term    = e($query);
        $escaped = preg_replace('/(' . preg_quote($term, '/') . ')/i', '<mark class="bg-yellow-200 dark:bg-yellow-800 rounded px-0.5">$1</mark>', $escaped);

        return $prefix . $escaped . '…';
    }
}
