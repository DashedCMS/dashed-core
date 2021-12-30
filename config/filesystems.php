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

        'qcommerce' => [
            'driver' => 'local',
            'root' => storage_path('app/public/qcommerce'),
            'url' => env('APP_URL') . '/storage/qcommerce',
            'visibility' => 'public',
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
