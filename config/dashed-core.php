<?php

return [
    'show_default_user_resource' => true,
    'default_auth_pages_enabled' => true,

    'sent_emails' => [
        'enabled' => env('DASHED_SENT_EMAILS_ENABLED', true),
        'retention_days' => (int) env('DASHED_SENT_EMAILS_RETENTION_DAYS', 90),
        'track_opens_clicks' => env('DASHED_SENT_EMAILS_TRACK', true),
        'postmark_webhook_secret' => env('POSTMARK_WEBHOOK_SECRET'),
    ],

    'blocks' => [
        'disable_caching' => env('DISABLE_BLOCK_CACHING', false),
        'caching_disabled' => [
            'contact-form',
        ],
        'relations' => [
            \Dashed\DashedPages\Models\Page::class => [ //if this model is updated, clear all blocks defined below
                'id' => 0,//or '*' for all or array of ids
                'blocks' => [ //Blocknames to clear
                    'block-name',
                ],
            ],
        ],
    ],

    'response_cache_enabled' => env('RESPONSE_CACHE_ENABLED', false),

    'site_theme' => env('SITE_THEME', 'dashed'),
    'site_id' => env('DASHED_SITE_ID'),

    'performance' => [
        'lazy_images_default' => env('DASHED_PERF_LAZY_IMAGES', true),
        'lazy_images_first_eager_count' => (int) env('DASHED_PERF_LAZY_FIRST_EAGER', 3),
        'defer_third_party_scripts' => env('DASHED_PERF_DEFER_SCRIPTS', true),
        'page_cache_enabled' => env('DASHED_PERF_PAGE_CACHE', false),
        'image_pipeline_v2' => env('DASHED_PERF_IMAGE_V2', false),
        'font_self_hosted' => env('DASHED_PERF_FONT_SELF', false),
    ],
];
