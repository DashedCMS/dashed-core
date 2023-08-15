<?php

return [
    'default' => env('FILESYSTEM_DRIVER', 'dashed-cdn'),

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

//        'dashed' => [
//            'driver' => 's3',
//            'visibility' => 'public',
//            'key' => env('DO_SPACES_KEY', ''),
//            'secret' => env('DO_SPACES_SECRET', ''),
//            'endpoint' => env('DO_SPACES_ENDPOINT', 'https://ams3.digitaloceanspaces.com'),
//            'region' => env('DO_SPACES_REGION', 'ams3'),
//            'bucket' => env('DO_SPACES_BUCKET', ''),
//        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
