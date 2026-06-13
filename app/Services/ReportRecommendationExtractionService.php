<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Str;

class ReportRecommendationExtractionService
{
    /**
     * @return array<int, array{recommendation: string, decision: ?string}>
     */
    public function extract(Report $report, bool $includeDecisions = true, int $maxItems = 10): array
    {
        $sources = [];

        $content = app(ReportExportService::class)->buildContent($report);
        if (filled($content)) {
            $sources[] = $content;
        }

        $metadata = $report->metadata_json ?? [];
        if (is_array($metadata) && ! empty($metadata)) {
            $sources[] = $this->flattenMetadata($metadata);
        }

        $explicit = $this->extractExplicitMetadataRecommendations($metadata);

        $candidates = array_merge($explicit, $this->extractFromText(implode("\n\n", $sources), $includeDecisions));

        // Deduplicate on recommendation text
        $seen = [];
        $unique = [];

        foreach ($candidates as $item) {
            $key = mb_strtolower(trim($item['recommendation']));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $item;
            if (count($unique) >= max(1, $maxItems)) {
                break;
            }
        }

        return $unique;
    }

    /**
     * @return array<int, array{recommendation: string, decision: ?string}>
     */
    private function extractExplicitMetadataRecommendations(array $metadata): array
    {
        $items = [];

        foreach (['recommendations', 'recommandations'] as $key) {
            if (! array_key_exists($key, $metadata)) {
                continue;
            }

            $value = $metadata[$key];

            if (is_array($value)) {
                foreach ($value as $entry) {
                    if (is_string($entry) && filled($entry)) {
                        $items[] = [
                            'recommendation' => trim($entry),
                            'decision' => null,
                        ];
                    }
                }
            }

            if (is_string($value) && filled($value)) {
                foreach (preg_split('/\r\n|\r|\n/', $value) as $line) {
                    if (filled($line)) {
                        $items[] = [
                            'recommendation' => trim($line),
                            'decision' => null,
                        ];
                    }
                }
            }
        }

        return $items;
    }

    /**
     * @return array<int, array{recommendation: string, decision: ?string}>
     */
    private function extractFromText(string $text, bool $includeDecisions): array
    {
        if (blank($text)) {
            return [];
        }

        $items = [];
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);

        // Prioritize content under a "Recommandations" section when present.
        if (preg_match('/recommandations?\s*:?[\t ]*\n(.+?)(\n\s*[A-Z][^\n]{0,60}:|\z)/isu', $normalized, $m)) {
            $normalized = trim($m[1]);
        }

        $lines = preg_split('/\n+/', $normalized) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Strip bullets/enumeration markers.
            $line = preg_replace('/^(?:[-*•]|\d+[\).]|[a-z]\))\s*/iu', '', $line) ?? $line;
            $line = trim($line);

            if (mb_strlen($line) < 12) {
                continue;
            }

            $lower = mb_strtolower($line);
            $looksLikeRecommendation = Str::contains($lower, [
                'recommand', 'il est recommande', 'doit', 'devrait', 'propose', 'action', 'mettre en place', 'renforcer', 'assurer',
            ]);

            if (! $looksLikeRecommendation) {
                continue;
            }

            $decision = null;

            if ($includeDecisions) {
                // Split patterns such as "... Decision: ..." or "... => ...".
                if (preg_match('/^(.*?)\s*(?:decision\s*:\s*|=>|->)\s*(.+)$/iu', $line, $parts)) {
                    $line = trim($parts[1]);
                    $decision = trim($parts[2]);
                }
            }

            $items[] = [
                'recommendation' => rtrim($line, " .;"),
                'decision' => $decision ?: null,
            ];
        }

        return $items;
    }

    private function flattenMetadata(array $metadata): string
    {
        $parts = [];

        foreach ($metadata as $key => $value) {
            if (is_scalar($value)) {
                $parts[] = (string) $key . ': ' . (string) $value;
                continue;
            }

            if (is_array($value)) {
                $parts[] = (string) $key . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return implode("\n", array_filter($parts));
    }
}
