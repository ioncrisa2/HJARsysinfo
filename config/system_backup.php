<?php

return [
    'root' => storage_path('app/backups'),
    'signing_key' => env('SYSTEM_BACKUP_SIGNING_KEY') ?: env('APP_KEY'),
    'restore_enabled' => (bool) env('SYSTEM_BACKUP_RESTORE_ENABLED', false),
    'database_restore_enabled' => (bool) env('SYSTEM_BACKUP_DATABASE_RESTORE_ENABLED', false),
    'mysql_binary' => env('MYSQL_BINARY', 'mysql'),
    'mysqldump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
    'process_timeout' => (int) env('SYSTEM_BACKUP_PROCESS_TIMEOUT', 3600),
    'max_package_megabytes' => (int) env('SYSTEM_BACKUP_MAX_PACKAGE_MB', 1024),
    'max_zip_entries' => (int) env('SYSTEM_BACKUP_MAX_ZIP_ENTRIES', 10000),
    'max_extracted_megabytes' => (int) env('SYSTEM_BACKUP_MAX_EXTRACTED_MB', 2048),
    'max_compression_ratio' => (int) env('SYSTEM_BACKUP_MAX_COMPRESSION_RATIO', 200),
    'retention_days' => (int) env('SYSTEM_BACKUP_RETENTION_DAYS', 30),
    'upload_roots' => [
        'foto_pembanding',
        'settings',
    ],
    'blocked_upload_extensions' => [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'cgi', 'pl', 'py', 'sh', 'bash', 'exe', 'dll', 'com', 'bat', 'cmd',
        'htaccess', 'user.ini',
    ],
    'allowed_upload_extensions' => [
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp',
    ],
];
