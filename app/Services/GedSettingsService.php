<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Schema;

class GedSettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = AppSetting::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public function documentTypes(): array
    {
        if (! Schema::hasTable('document_types')) {
            return config('acquisition.document_types', []);
        }

        $types = DocumentType::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $types = array_values(array_filter(array_map(fn ($t) => trim((string) $t), $types)));

        if ($types === []) {
            return config('acquisition.document_types', []);
        }

        return array_combine($types, $types);
    }

    public function maxFileSizeMb(): int
    {
        return (int) $this->get('ged.max_file_size_mb', (int) config('acquisition.max_file_size_mb', 50));
    }

    public function uploadQuotaMb(): int
    {
        return (int) $this->get('ged.upload_quota_mb', (int) config('courriers.upload_quota_mb', 500));
    }

    public function lifecycle(): array
    {
        $default = [
            'courrier_archive_after_days' => (int) config('courriers.lifecycle.courrier_archive_after_days', 90),
            'document_archive_after_days' => (int) config('courriers.lifecycle.document_archive_after_days', 365),
        ];

        $value = $this->get('ged.lifecycle', $default);

        return is_array($value) ? array_merge($default, $value) : $default;
    }

    public function retentionByType(): array
    {
        $default = [
            'Contrat' => 10,
            'Decision' => 10,
            'Facture' => 10,
            'Bon commande' => 10,
            'Rapport activite' => 7,
            'Rapport mission' => 7,
            'PV reunion' => 7,
            'Procedure' => 7,
            'Note service' => 5,
            'Note information' => 5,
            'Compte-rendu' => 5,
            'Courrier entrant' => 5,
            'Courrier sortant' => 5,
            'Autre' => 5,
        ];

        $value = $this->get('ged.retention_by_type', $default);

        return is_array($value) ? $value : $default;
    }

    public function referenceConfig(): array
    {
        $default = [
            'auto_enabled' => true,
            'default_mode' => 'saisir',
            'format' => 'DOC/{TYPE_CODE}/{YYYY}/{SEQ}',
            'sequence_scope' => 'yearly',
            'sequence_padding' => 4,
            'type_modes' => [],
        ];

        $value = $this->get('ged.reference', $default);

        if (! is_array($value)) {
            return $default;
        }

        $config = array_merge($default, $value);

        $config['default_mode'] = ($config['default_mode'] ?? 'saisir') === 'generer' ? 'generer' : 'saisir';
        $config['sequence_scope'] = in_array(($config['sequence_scope'] ?? 'yearly'), ['global', 'yearly', 'monthly'], true)
            ? $config['sequence_scope']
            : 'yearly';
        $config['sequence_padding'] = max(1, min(10, (int) ($config['sequence_padding'] ?? 4)));
        $config['auto_enabled'] = (bool) ($config['auto_enabled'] ?? true);
        $config['type_modes'] = is_array($config['type_modes'] ?? null) ? $config['type_modes'] : [];

        return $config;
    }

    /**
     * Returns true if the scheduled IMAP import job is enabled.
     * Defaults to true when an IMAP host is configured, false otherwise.
     */
    public function imapScheduleEnabled(): bool
    {
        return (bool) $this->get(
            'acquisition.imap_schedule_enabled',
            ! empty(config('acquisition.imap.host'))
        );
    }
}
