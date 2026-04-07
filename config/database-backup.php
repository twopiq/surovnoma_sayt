<?php

return [
    'enabled' => env('DB_BACKUP_ENABLED', true),
    'keep' => (int) env('DB_BACKUP_KEEP', 30),
    'path' => env('DB_BACKUP_PATH', storage_path('app/backups/database')),
];
