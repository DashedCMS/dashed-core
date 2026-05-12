<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log channel
    |--------------------------------------------------------------------------
    |
    | Auto-registered Customsetting keys are logged at info level on this
    | channel. Set to null to disable.
    |
    */
    'log_channel' => env('DASHED_SETTINGS_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'report_dir' => storage_path('logs'),
    ],
];
