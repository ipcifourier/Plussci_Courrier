<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentReferenceSequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentReferenceService
{
    public const MODE_GENERATE = 'generer';
    public const MODE_MANUAL = 'saisir';

    public function __construct(
        private readonly GedSettingsService $settings,
    ) {
    }

    public function normalizeMode(?string $mode): string
    {
        return $mode === self::MODE_GENERATE ? self::MODE_GENERATE : self::MODE_MANUAL;
    }

    public function isAutoEnabled(): bool
    {
        return (bool) data_get($this->settingsConfig(), 'auto_enabled', true);
    }

    public function defaultMode(): string
    {
        return $this->normalizeMode((string) data_get($this->settingsConfig(), 'default_mode', self::MODE_MANUAL));
    }

    public function modeForType(?string $type): string
    {
        $typeModes = data_get($this->settingsConfig(), 'type_modes', []);

        if (is_array($typeModes) && $type) {
            $configured = $typeModes[$type] ?? null;
            if (is_string($configured)) {
                return $this->normalizeMode($configured);
            }
        }

        return $this->defaultMode();
    }

    public function shouldGenerate(?string $mode, ?string $type): bool
    {
        if (! $this->isAutoEnabled()) {
            return false;
        }

        $effectiveMode = $mode ? $this->normalizeMode($mode) : $this->modeForType($type);

        return $effectiveMode === self::MODE_GENERATE;
    }

    public function preview(?string $type = null, ?Carbon $at = null): string
    {
        $at ??= now();

        $padding = $this->sequencePadding();
        $scopeKey = $this->scopeKey($type, $at);
        $current = (int) (DocumentReferenceSequence::query()->where('scope_key', $scopeKey)->value('current_value') ?? 0);
        $next = $current + 1;

        return $this->renderReference($type, $at, $next, $padding);
    }

    public function generate(?string $type = null, ?Carbon $at = null): string
    {
        $at ??= now();

        return DB::transaction(function () use ($type, $at): string {
            $padding = $this->sequencePadding();
            $scopeKey = $this->scopeKey($type, $at);

            $sequence = DocumentReferenceSequence::query()
                ->where('scope_key', $scopeKey)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = DocumentReferenceSequence::query()->create([
                    'scope_key' => $scopeKey,
                    'current_value' => 0,
                ]);

                $sequence->refresh();
            }

            do {
                $next = ((int) $sequence->current_value) + 1;
                $sequence->update(['current_value' => $next]);

                $candidate = $this->renderReference($type, $at, $next, $padding);
                $exists = Document::query()->where('reference_doc', $candidate)->exists();
            } while ($exists);

            return $candidate;
        }, 5);
    }

    public function ensureReference(array $data): array
    {
        $type = isset($data['type_document']) ? (string) $data['type_document'] : null;
        $mode = isset($data['reference_mode']) ? (string) $data['reference_mode'] : null;

        $effectiveMode = $mode ? $this->normalizeMode($mode) : $this->modeForType($type);
        $data['reference_mode'] = $effectiveMode;

        if (! $this->shouldGenerate($effectiveMode, $type)) {
            return $data;
        }

        $reference = trim((string) ($data['reference_doc'] ?? ''));

        if ($reference !== '') {
            return $data;
        }

        $data['reference_doc'] = $this->generate($type);

        return $data;
    }

    public function settingsConfig(): array
    {
        $config = $this->settings->referenceConfig();
        $config['default_mode'] = $this->normalizeMode((string) ($config['default_mode'] ?? self::MODE_MANUAL));
        $config['sequence_scope'] = $this->normalizeScope((string) ($config['sequence_scope'] ?? 'yearly'));
        $config['sequence_padding'] = $this->normalizePadding((int) ($config['sequence_padding'] ?? 4));

        if (! is_array($config['type_modes'])) {
            $config['type_modes'] = [];
        }

        return $config;
    }

    private function sequencePadding(): int
    {
        return $this->normalizePadding((int) data_get($this->settingsConfig(), 'sequence_padding', 4));
    }

    private function normalizePadding(int $padding): int
    {
        return max(1, min(10, $padding));
    }

    private function normalizeScope(string $scope): string
    {
        return in_array($scope, ['global', 'yearly', 'monthly'], true) ? $scope : 'yearly';
    }

    private function scopeKey(?string $type, Carbon $at): string
    {
        $scope = $this->normalizeScope((string) data_get($this->settingsConfig(), 'sequence_scope', 'yearly'));
        $typeKey = $this->typeCode($type);

        return match ($scope) {
            'monthly' => 'docref:' . $typeKey . ':' . $at->format('Ym'),
            'global' => 'docref:' . $typeKey . ':global',
            default => 'docref:' . $typeKey . ':' . $at->format('Y'),
        };
    }

    private function renderReference(?string $type, Carbon $at, int $sequence, int $padding): string
    {
        $template = (string) data_get($this->settingsConfig(), 'format', 'DOC/{TYPE_CODE}/{YYYY}/{SEQ}');

        $replacements = [
            '{TYPE}' => trim((string) ($type ?? 'DOCUMENT')),
            '{TYPE_CODE}' => $this->typeCode($type),
            '{YYYY}' => $at->format('Y'),
            '{YY}' => $at->format('y'),
            '{MM}' => $at->format('m'),
            '{SEQ}' => str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT),
        ];

        $value = strtr($template, $replacements);

        return strtoupper(trim($value));
    }

    private function typeCode(?string $type): string
    {
        $base = trim((string) ($type ?? 'DOC'));

        if ($base === '') {
            return 'DOC';
        }

        $ascii = Str::upper(Str::ascii($base));
        $clean = preg_replace('/[^A-Z0-9]+/', '_', $ascii) ?? 'DOC';
        $clean = trim($clean, '_');

        return $clean !== '' ? $clean : 'DOC';
    }
}
