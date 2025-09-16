<?php

return [
    'show_default_user_resource' => true,
    'default_auth_pages_enabled' => true,

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

    'site_theme' => env('SITE_THEME', 'dashed'),
    'site_id' => env('DASHED_SITE_ID'),
];
