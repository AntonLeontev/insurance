<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'snapshots' => [
            'driver' => 'local',
            'root' => storage_path('app/snapshots'),
        ],

        'backups' => [
            'driver' => 's3',
            'key' => env('BACKUP_AWS_ACCESS_KEY_ID'),
            'secret' => env('BACKUP_AWS_SECRET_ACCESS_KEY'),
            'region' => env('BACKUP_AWS_DEFAULT_REGION', 'ru-central-1'),
            'bucket' => env('BACKUP_AWS_BUCKET'),
            'url' => env('BACKUP_AWS_URL'),
            'endpoint' => env('BACKUP_AWS_ENDPOINT', 'https://s3.cloud.ru'),
            'use_path_style_endpoint' => filter_var(env('BACKUP_AWS_USE_PATH_STYLE_ENDPOINT', true), FILTER_VALIDATE_BOOLEAN),
            'root' => trim((string) env('BACKUP_DIRECTORY', ''), '/'),
            'throw' => true,
        ],

        'backups_vk' => [
            'driver' => 's3',
            'key' => env('BACKUP_VK_AWS_ACCESS_KEY_ID'),
            'secret' => env('BACKUP_VK_AWS_SECRET_ACCESS_KEY'),
            'region' => env('BACKUP_VK_AWS_DEFAULT_REGION', 'ru-msk'),
            'bucket' => env('BACKUP_VK_AWS_BUCKET'),
            'url' => env('BACKUP_VK_AWS_URL'),
            'endpoint' => env('BACKUP_VK_AWS_ENDPOINT', 'https://hb.ru-msk.vkcloud-storage.ru'),
            'use_path_style_endpoint' => filter_var(env('BACKUP_VK_AWS_USE_PATH_STYLE_ENDPOINT', true), FILTER_VALIDATE_BOOLEAN),
            'root' => trim((string) env('BACKUP_DIRECTORY', ''), '/'),
            'throw' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
