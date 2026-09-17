<?php

return [
    'show_default_user_resource' => true,
    'default_auth_pages_enabled' => true,

    'sent_emails' => [
        'enabled' => env('DASHED_SENT_EMAILS_ENABLED', true),
        'retention_days' => (int) env('DASHED_SENT_EMAILS_RETENTION_DAYS', 90),
        'track_opens_clicks' => env('DASHED_SENT_EMAILS_TRACK', true),
        'postmark_webhook_secret' => env('POSTMARK_WEBHOOK_SECRET'),
        // Mails waarvan de body nooit in het logboek komt (alleen metadata):
        // ze bevatten per definitie een wachtwoord, code of inloglink.
        'withhold_body_for' => [
            \Dashed\DashedCore\Mail\NewAdminAccountMail::class,
            \Dashed\DashedCore\Mail\PasswordResetMail::class,
            \Illuminate\Auth\Notifications\ResetPassword::class,
            'Filament\\Auth\\Notifications\\ResetPassword',
            'Filament\\Auth\\MultiFactor\\Email\\Notifications\\VerifyEmailAuthentication',
        ],
        // Mailgegevens met deze naam worden in de body van elke andere mail gemaskeerd.
        'secret_keys' => ['password', 'plaintextPassword', 'pin', 'code', 'otp', 'token', 'secret', 'passwordResetToken'],
        // Querystring-parameters in links waarvan de waarde wordt weggehaald.
        'secret_query_params' => ['token', 'signature', 'passwordResetToken', 'code', 'otp'],
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

    'edge_purge' => [
        // Minimaal aantal seconden tussen twee Cloudflare zone-purges per site.
        // Binnen een venster gaat de eerste purge meteen door en volgt er hooguit
        // één naloper, zodat de laatste wijziging alsnog landt. Op 0 zetten
        // schakelt de throttle uit; elke wijziging purgt dan weer de hele zone.
        'window_seconds' => (int) env('DASHED_EDGE_PURGE_WINDOW_SECONDS', 300),
    ],

    // Welke Host-headers dit systeem mogen aanspreken, naast de host van
    // APP_URL en de site_url per site. Exact, zonder schema, komma-gescheiden;
    // bijvoorbeeld een staging-domein. Zie Middleware\TrustedHosts.
    'trusted_hosts' => [
        'extra' => array_values(array_filter(array_map('trim', explode(',', (string) env('DASHED_TRUSTED_HOSTS', ''))))),
        // null: zoals Laravel zelf, afdwingen buiten local en buiten tests.
        // Op false zetten is de ontsnapping als een uitrol zichzelf buitensluit
        // (verkeerde APP_URL); op true dwingt ook lokaal en in tests af.
        'enforce' => env('DASHED_TRUSTED_HOSTS_ENFORCE'),
    ],

    // Wachtwoordeisen, overal waar een wachtwoord gezet wordt (website en CMS),
    // via Password::defaults(). Inloggen met een bestaand korter wachtwoord
    // blijft werken; de eis geldt bij het kiezen van een nieuw wachtwoord.
    'passwords' => [
        'min_length' => (int) env('DASHED_PASSWORD_MIN_LENGTH', 8),
        // Alleen in productie: controle tegen bekende datalekken (HaveIBeenPwned,
        // k-anonimiteit, het wachtwoord zelf gaat de deur niet uit).
        'uncompromised' => env('DASHED_PASSWORD_UNCOMPROMISED', true),
    ],

    // HTTP-beveiligingsheaders op elk antwoord (website en CMS). Een header op
    // null of een lege string wordt niet gezet. HSTS gaat alleen mee over
    // https en buiten local. Zie Middleware\SecurityHeaders.
    'security_headers' => [
        'enabled' => env('DASHED_SECURITY_HEADERS', true),
        'strict_transport_security' => env('DASHED_HSTS', 'max-age=31536000; includeSubDomains'),
        'x_frame_options' => env('DASHED_X_FRAME_OPTIONS', 'SAMEORIGIN'),
        'x_content_type_options' => env('DASHED_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'referrer_policy' => env('DASHED_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('DASHED_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=(), payment=(self)'),
    ],

    // Proxy's waarvan X-Forwarded-For en X-Forwarded-Proto vertrouwd worden.
    // Komma-gescheiden adressen of reeksen, of * voor elke proxy. Verplicht
    // achter Cloudflare of een load balancer, anders ziet de applicatie het
    // adres van de proxy: dan werkt de IP-lijst van het CMS niet zoals
    // bedoeld, delen alle bezoekers een verzoeklimiet, en is elke link http.
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) env('DASHED_TRUSTED_PROXIES', ''))))),

    // Ontvangers van beveiligingsmeldingen (login vanaf nieuw IP, mislukte
    // inlogpoging op een beheerdersaccount). Gezet in .env, komma-gescheiden:
    // dan gaan die meldingen uitsluitend hierheen en telt de lijst bij
    // Instellingen, Beveiliging niet meer mee. Zo kan iemand met toegang tot
    // het CMS de meldingen niet omleiden. Leeg: die lijst, en zonder lijst
    // alle superadmins. Zie Classes\SecurityAlerts.
    'security_alert_recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('SECURITY_ALERT_RECIPIENTS', ''))))),

    'dashed_cms' => [
        'path' => env('DASHED_CMS_PATH', 'dashed'),
        'primary_color' => env('DASHED_CMS_PRIMARY_COLOR', '#00D2CD'),
        // Op false: geen wachtwoord-vergeten in het CMS-panel (routes en link
        // op het loginscherm verdwijnen; er gaan geen resetmails meer uit).
        'password_reset_enabled' => env('DASHED_CMS_PASSWORD_RESET_ENABLED', true),
    ],

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
