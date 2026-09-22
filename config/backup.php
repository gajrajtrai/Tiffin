<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Database + uploaded files. Retention: 7 daily, 8 weekly, 4 monthly,
    | 2 yearly. Hard cap at 5 GB. Latest backup is never deleted.
    |
    */

    'backup' => [

        'name' => env('APP_NAME', 'chula-tiffins'),

        'source' => [

            'files' => [
                'include' => [
                    storage_path('app'),
                ],
                'exclude' => [
                    storage_path('app/backups'),
                    storage_path('app/Laravel'),
                    storage_path('app/private/livewire-tmp'),
                    storage_path('framework'),
                    storage_path('logs'),
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => true,
                'relative_path' => null,
            ],

            'databases' => [
                'mariadb',
            ],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',
        'database_dump_compressor_extension' => '',

        'destination' => [

            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => '',
            'disks' => [
                'local',
            ],
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),

        'encryption' => 'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Disabled for LAN-only deployment (no reliable outbound email).
    | Health checks are run manually or via the admin panel.
    |
    */

    'notifications' => [

        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => 'admin@example.com',
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Backup'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Health Monitoring
    |--------------------------------------------------------------------------
    */

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'chula-tiffins'),
            'disks' => ['local'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup / Retention
    |--------------------------------------------------------------------------
    |
    | Rule order:
    |   1. Never delete the latest backup
    |   2. Keep ALL backups from the last 7 days
    |   3. Keep DAILY backups for 16 days
    |   4. Keep WEEKLY backups for 8 weeks
    |   5. Keep MONTHLY backups for 4 months
    |   6. Keep YEARLY backups for 2 years
    |   7. Delete oldest if total > 5000 MB
    |
    */

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],
];