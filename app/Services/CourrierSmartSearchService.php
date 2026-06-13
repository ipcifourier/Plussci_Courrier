<?php

namespace App\Services;

class CourrierSmartSearchService
{
    /**
     * @return array{query: array<string, string>, summary: string}
     */
    public function buildListQuery(string $input): array
    {
        $raw = trim($input);
        $text = mb_strtolower($raw);

        $query = [];
        $summary = [];

        // Keep original user text as table search by default.
        if ($raw !== '') {
            $query['tableSearch'] = $raw;
            $summary[] = 'Texte libre';
        }

        // Type detection.
        if ($this->containsAny($text, ['entrant', 'recu', 'reception'])) {
            $query['tableFilters[type][value]'] = 'Entrant';
            $summary[] = 'Type: Entrant';
        } elseif ($this->containsAny($text, ['sortant', 'envoi', 'envoye'])) {
            $query['tableFilters[type][value]'] = 'Sortant';
            $summary[] = 'Type: Sortant';
        }

        // Statut detection.
        if ($this->containsAny($text, ['nouveau', 'non traite'])) {
            $query['tableFilters[statut][value]'] = 'Nouveau';
            $summary[] = 'Statut: Nouveau';
        } elseif ($this->containsAny($text, ['en cours', 'encours'])) {
            $query['tableFilters[statut][value]'] = 'En cours';
            $summary[] = 'Statut: En cours';
        } elseif ($this->containsAny($text, ['traite', 'termine'])) {
            $query['tableFilters[statut][value]'] = 'Traité';
            $summary[] = 'Statut: Traité';
        } elseif ($this->containsAny($text, ['archive', 'archivé'])) {
            $query['tableFilters[statut][value]'] = 'Archivé';
            $summary[] = 'Statut: Archivé';
        }

        // Priority.
        if ($this->containsAny($text, ['urgent', 'urgence'])) {
            $query['tableFilters[priorite][value]'] = 'Urgente';
            $summary[] = 'Priorité: Urgente';
        }

        // Approval.
        if ($this->containsAny($text, ['a valider', 'à valider', 'validation en attente'])) {
            $query['tableFilters[approval_status][value]'] = 'pending';
            $summary[] = 'Validation: En attente';
        }

        // Signed.
        if ($this->containsAny($text, ['signe', 'signé', 'signature'])) {
            $query['tableFilters[signed_only][isActive]'] = '1';
            $summary[] = 'Signés uniquement';
        }

        // Overdue.
        if ($this->containsAny($text, ['retard', 'en retard', 'delai depasse'])) {
            $query['tableFilters[en_retard][isActive]'] = '1';
            $summary[] = 'En retard';
        }

        // Year extraction.
        if (preg_match('/\b(20\d{2})\b/', $raw, $m)) {
            $year = $m[1];
            $query['tableFilters[date_reception_envoi][date_debut]'] = $year . '-01-01';
            $query['tableFilters[date_reception_envoi][date_fin]'] = $year . '-12-31';
            $summary[] = 'Année: ' . $year;
        }

        return [
            'query' => $query,
            'summary' => empty($summary) ? 'Recherche texte libre' : implode(' | ', $summary),
        ];
    }

    /**
     * @param list<string> $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
