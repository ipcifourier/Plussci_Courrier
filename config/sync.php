<?php

return [
    'enabled' => env('SYNC_ENABLED', true),
    'default_interval_minutes' => (int) env('SYNC_DEFAULT_INTERVAL_MINUTES', 15),
    'max_files_per_pull' => (int) env('SYNC_MAX_FILES_PER_PULL', 200),
    'allow_upload' => env('SYNC_ALLOW_UPLOAD', false),
];
