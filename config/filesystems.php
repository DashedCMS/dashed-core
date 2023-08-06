<?php

return [
    'default' => env('FILESYSTEM_DRIVER', 'public'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        'dashed' => [
            'driver' => 'local',
            'root' => storage_path('app/public/dashed'),
            'url' => env('APP_URL') . '/storage/dashed',
            'visibility' => 'public',
        ],

        'dashed-uploads' => [
            'driver' => 'local',
            'root' => storage_path('app/public/dashed/uploads'),
            'url' => env('APP_URL') . '/storage/dashed/uploads',
            'visibility' => 'public',
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
