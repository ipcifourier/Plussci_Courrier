<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Carbon;

/**
 * Analyses OCR text (and filename) to automatically:
 *  - Detect document type
 *  - Extract keywords / tags
 *  - Extract structured metadata (dates, numbers, amounts)
 */
class AutoClassificationService
{
    // ─── Type-detection rules ────────────────────────────────────────────────
    // Each entry: [type_key, [regex patterns], weight]
    private const TYPE_RULES = [
        ['Rapport activite',  ['rapport\s+d\s*activit', 'bilan\s+annuel', 'rapport\s+annuel'], 3],
        ['Rapport mission',   ['rapport\s+de\s+mission', 'compte[- ]rendu\s+de\s+mission'], 3],
        ['PV reunion',        ['proc[eè]s[- ]verbal', '\bpv\s+r[eé]union\b', '\bpv\s+du\b'], 3],
        ['Compte-rendu',      ['compte[- ]rendu', '\bcr\s+r[eé]union\b', 'r[eé]sum[eé]\s+r[eé]union'], 2],
        ['Note service',      ['note\s+de\s+service', '\bn\.?s\.?\s+n[°o]', 'note\s+interne'], 2],
        ['Note information',  ['note\s+d\s*information', '\bn\.?i\.?\s'], 2],
        ['Contrat',           ['contrat\s+de', '\bconvention\s+de\b', 'accord[- ]cadre', '\bmarché\b'], 2],
        ['Facture',           ['bon\s+à\s+payer', '\bfacturette\b', '\bfacture\s+n[°o]', 'montant\s+ttc', 'total\s+ht'], 2],
        ['Bon commande',      ['bon\s+de\s+commande', '\bbc\s+n[°o]', 'commande\s+n[°o]'], 2],
        ['Decision',          ['\bdécision\s+n[°o]', '\barrêté\s+n[°o]', '\bordre\s+de\s+service\b'], 2],
        ['Procedure',         ['proc[eé]dure\s+de', 'guide\s+d\s*utilisation', 'manuel\s+de'], 2],
        ['Courrier entrant',  ['ci-joint', 'j\'ai l\'honneur', 'veuillez\s+trouver', 'en\s+réponse\s+à'], 1],
        ['Courrier sortant',  ['objet\s*:', 'ref[eé]rence\s*:', 'destinataire\s*:', 'à\s+l\'attention\s+de'], 1],
    ];

    // ─── Stopwords (French + English) ────────────────────────────────────────
    private const STOPWORDS = [
        'le','la','les','un','une','des','du','de','et','en','à','au','aux',
        'par','sur','pour','dans','avec','que','qui','quoi','dont','ou','si',
        'il','elle','ils','elles','nous','vous','on','me','te','se','lui',
        'ce','cette','ces','mon','ma','mes','ton','ta','tes','son','sa','ses',
        'pas','plus','très','bien','aussi','comme','mais','donc','car','ni',
        'the','of','and','to','in','is','it','that','for','with','are','this',
        'was','be','as','at','by','from','or','an','not','which','have','were',
        'has','his','her','they','their','had','but','you','all','can','been',
        'will','said','we','its','about','than','when','who','what','how',
    ];

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Classify a document and persist the results.
     *
     * @return array{type_document: string, tags: string[], keywords: string, metadata: array, confidence: int}
     */
    public function classify(Document $document): array
    {
        $text = $this->gatherText($document);

        $type       = $this->detectType($text, $document->titre, basename($document->reference_doc ?? ''));
        $tags       = $this->extractTags($text, $document->titre);
        $keywords   = implode(' ', $tags);
        $metadata   = $this->extractMetadata($text);
        $confidence = $type['confidence'];

        // Only override type if auto-detected with enough confidence and
        // current type is generic 'Document' or 'Autre'
        $newType = $document->type_document;
        if ($confidence >= 60 && in_array($document->type_document, ['Document', 'Autre', ''], true)) {
            $newType = $type['type'];
        }

        $mergedTags     = $this->mergeTags($document->tags_json ?? [], $tags);
        $mergedMeta     = array_merge($document->metadata_json ?? [], $metadata);

        $document->update([
            'type_document'            => $newType,
            'tags_json'                => $mergedTags,
            'metadata_json'            => $mergedMeta,
            'keywords'                 => mb_substr($keywords, 0, 65535),
            'classification_confidence' => $confidence,
            'classified_at'            => now(),
        ]);

        return [
            'type_document' => $newType,
            'tags'          => $mergedTags,
            'keywords'      => $keywords,
            'metadata'      => $mergedMeta,
            'confidence'    => $confidence,
        ];
    }

    // ─── Type detection ──────────────────────────────────────────────────────

    /**
     * Returns ['type' => string, 'confidence' => int (0-100)]
     */
    public function detectType(string $text, string $titre = '', string $filename = ''): array
    {
        $haystack = mb_strtolower($text . ' ' . $titre . ' ' . $filename);
        $scores   = [];

        foreach (self::TYPE_RULES as [$typeName, $patterns, $weight]) {
            $count = 0;
            foreach ($patterns as $pattern) {
                if (preg_match('/' . $pattern . '/iu', $haystack)) {
                    $count++;
                }
            }
            if ($count > 0) {
                $scores[$typeName] = ($scores[$typeName] ?? 0) + ($count * $weight);
            }
        }

        if (empty($scores)) {
            return ['type' => 'Autre', 'confidence' => 0];
        }

        arsort($scores);
        $best   = array_key_first($scores);
        $max    = 3 * 3; // max possible per type (3 patterns × weight 3)
        $confidence = (int) min(100, round(($scores[$best] / $max) * 100));

        return ['type' => $best, 'confidence' => $confidence];
    }

    // ─── Keyword / tag extraction ────────────────────────────────────────────

    /**
     * Extract significant keywords from text + title.
     * Returns an array of unique lowercase keywords.
     *
     * @return string[]
     */
    public function extractTags(string $text, string $titre = '', int $maxTags = 15): array
    {
        $combined = $titre . ' ' . $text;

        // Normalise: lowercase, strip accents naive-style, keep letters + digits
        $cleaned = mb_strtolower($combined);
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleaned);

        // Tokenise
        $words = preg_split('/\s+/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);

        // Filter: length >= 4, not stopword
        $stopwords = array_flip(self::STOPWORDS);
        $words = array_filter($words, static function (string $w) use ($stopwords): bool {
            return mb_strlen($w) >= 4 && ! isset($stopwords[$w]);
        });

        // Frequency count
        $freq = array_count_values($words);
        arsort($freq);

        // Return top N unique keywords appearing at least twice (or top 5 if few)
        $threshold = max(2, (int) (count($words) / 100));
        $top = [];
        foreach ($freq as $word => $count) {
            if ($count >= $threshold || count($top) < 5) {
                $top[] = $word;
            }
            if (count($top) >= $maxTags) {
                break;
            }
        }

        return $top;
    }

    // ─── Metadata extraction ─────────────────────────────────────────────────

    /**
     * Extract structured metadata from raw text:
     *  - dates_found: array of date strings
     *  - amounts_found: array of monetary amounts
     *  - references_found: array of reference numbers
     *
     * @return array
     */
    public function extractMetadata(string $text): array
    {
        $meta = [];

        // ── Dates ─────────────────────────────────────────────────────────────
        preg_match_all('/\b(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}|\d{4}[\/\-\.]\d{2}[\/\-\.]\d{2})\b/', $text, $dateMatches);
        if (! empty($dateMatches[1])) {
            $meta['dates_trouvees'] = array_values(array_unique(array_slice($dateMatches[1], 0, 5)));
        }

        // ── Monetary amounts ─────────────────────────────────────────────────
        preg_match_all('/(\d[\d\s]*(?:[,\.]\d{1,2})?\s*(?:FCFA|CFA|€|F\.CFA|XOF|USD|\$))/iu', $text, $amountMatches);
        if (! empty($amountMatches[1])) {
            $meta['montants_trouves'] = array_values(array_unique(array_slice(
                array_map('trim', $amountMatches[1]),
                0, 5
            )));
        }

        // ── Reference numbers (format: XX-NNNN/YYYY) ─────────────────────────
        preg_match_all('/\b([A-Z]{1,6}[\/\-\.]\d{3,6}(?:[\/\-\.]\d{2,4})?)\b/', $text, $refMatches);
        if (! empty($refMatches[1])) {
            $meta['refs_trouvees'] = array_values(array_unique(array_slice($refMatches[1], 0, 5)));
        }

        return $meta;
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function gatherText(Document $document): string
    {
        // Latest version OCR text
        $version = $document->currentVersion;
        if (! $version && $document->versions()->exists()) {
            $version = $document->versions()->latest('id')->first();
        }

        return ($version?->ocr_text ?? '') . ' ' . ($document->titre ?? '');
    }

    private function mergeTags(array $existing, array $newTags): array
    {
        $merged = array_values(array_unique(array_merge($existing, $newTags)));
        return array_slice($merged, 0, 20);
    }
}
