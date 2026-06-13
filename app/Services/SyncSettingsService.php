<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;

class SyncSettingsService
{
    public function globalConfig(): array
    {
        $defaults = [
            'enabled' => (bool) config('sync.enabled', true),
            'default_interval_minutes' => (int) config('sync.default_interval_minutes', 15),
            'max_files_per_pull' => (int) config('sync.max_files_per_pull', 200),
            'allow_upload' => (bool) config('sync.allow_upload', false),
        ];

        $setting = AppSetting::query()->where('key', 'sync.global')->first();

        $stored = is_array($setting?->value) ? $setting->value : [];

        return [
            'enabled' => (bool) ($stored['enabled'] ?? $defaults['enabled']),
            'default_interval_minutes' => min(120, max(1, (int) ($stored['default_interval_minutes'] ?? $defaults['default_interval_minutes']))),
            'max_files_per_pull' => min(1000, max(10, (int) ($stored['max_files_per_pull'] ?? $defaults['max_files_per_pull']))),
            'allow_upload' => (bool) ($stored['allow_upload'] ?? $defaults['allow_upload']),
        ];
    }

    public function userConfig(User $user): array
    {
        $prefs = is_array($user->preferences) ? $user->preferences : [];
        $sync = is_array($prefs['sync'] ?? null) ? $prefs['sync'] : [];
        $global = $this->globalConfig();

        return [
            'enabled' => (bool) ($sync['enabled'] ?? true),
            'interval_minutes' => min(120, max(1, (int) ($sync['interval_minutes'] ?? $global['default_interval_minutes']))),
            'conflict_policy' => in_array(($sync['conflict_policy'] ?? 'keep_both'), ['keep_both', 'server_wins', 'local_wins'], true)
                ? (string) $sync['conflict_policy']
                : 'keep_both',
            'download_on_metered' => (bool) ($sync['download_on_metered'] ?? false),
            'auto_start' => (bool) ($sync['auto_start'] ?? true),
        ];
    }
}
