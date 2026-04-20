<?php
cms()->checkModelPassword($model ?? null);

if (is_numeric(seo()->metaData('metaImage'))) {
    seo()->metaData('metaImage', mediaHelper()->getSingleMedia(seo()->metaData('metaImage'), 'original')->url ?? '');
}
?><?php
$tracking = $trackingSettings ?? [];

$gtmId = $tracking['google_tagmanager_id'] ?? null;
$gaId = $tracking['google_analytics_id'] ?? null;

$fbConversionId = $tracking['facebook_pixel_conversion_id'] ?? null;
$fbSiteId = $tracking['facebook_pixel_site_id'] ?? null;
$facebookEnabled = !empty($fbConversionId) || !empty($fbSiteId);

$extraScripts = $extraHeadScripts ?? '';
$ogSiteName = $siteName ?? config('app.name');
?>

@if(config('dashed-core.performance.web_vitals_enabled'))
    @php
        $perfSiteId = (int) (\Dashed\DashedCore\Classes\Sites::getActive() ?? 0);
    @endphp
    <script>window.dashedSiteId = {{ $perfSiteId }};</script>
    <script type="module">
        import { onLCP, onCLS, onINP, onFCP, onTTFB } from 'https://unpkg.com/web-vitals@4?module';

        const ENDPOINT = '/_dashed/perf/vitals';

        const beacon = (metric) => {
            if (typeof navigator === 'undefined' || typeof navigator.sendBeacon !== 'function') return;
            const payload = JSON.stringify({
                name: metric.name,
                value: metric.value,
                rating: metric.rating,
                url: window.location.pathname,
                device: /Mobi/i.test(navigator.userAgent) ? 'mobile' : 'desktop',
                site: window.dashedSiteId || null,
            });
            try {
                navigator.sendBeacon(ENDPOINT, new Blob([payload], { type: 'application/json' }));
            } catch (e) {}
        };

        [onLCP, onCLS, onINP, onFCP, onTTFB].forEach(fn => fn(beacon));
    </script>
@endif

@if(app()->isProduction())
    @if($gtmId)
        <script>
            (function (w, d, s, l, i) {
                w[l] = w[l] || [];
                w[l].push({
                    'gtm.start': new Date().getTime(), event: 'gtm.js'
                });
                var f = d.getElementsByTagName(s)[0],
                    j = d.createElement(s),
                    dl = l !== 'dataLayer' ? '&l=' + l : '';
                j.async = true;
                j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', '{{ $gtmId }}');
        </script>
    @endif

    @if($gaId)
        @if(config('dashed-core.performance.defer_third_party_scripts'))
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
            @php
                app(\Dashed\DashedCore\Performance\Scripts\DeferredScriptStore::class)->add('ga-config',
                    "window.dataLayer = window.dataLayer || [];\n" .
                    "function gtag(){dataLayer.push(arguments);}\n" .
                    "gtag('js', new Date());\n" .
                    "gtag('config', '" . $gaId . "');"
                );
            @endphp
        @else
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag() { dataLayer.push(arguments); }
                gtag('js', new Date());
                gtag('config', '{{ $gaId }}');
            </script>
        @endif
    @endif
@endif

{!! $extraScripts !!}

@if($favicon)
    <link rel="apple-touch-icon" sizes="57x57" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [57, 57]])->url ?? '' }}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [60, 60]])->url ?? '' }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [72, 72]])->url ?? '' }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [76, 76]])->url ?? '' }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [114, 114]])->url ?? '' }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [120, 120]])->url ?? '' }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [144, 144]])->url ?? '' }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [152, 152]])->url ?? '' }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [180, 180]])->url ?? '' }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [16, 16]])->url ?? '' }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [32, 32]])->url ?? '' }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [96, 96]])->url ?? '' }}">
    <link rel="icon" type="image/png" sizes="128x128" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [128, 128]])->url ?? '' }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ mediaHelper()->getSingleMedia($favicon, ['fit' => [192, 192]])->url ?? '' }}">
@endif

@if(isset($model))
    {!! $model->metaData->head_scripts ?? '' !!}
@endif

<title>{{ seo()->metaData('metaTitle') }}</title>

@isset($model)
    @php
        $hreflangLocales = \Dashed\DashedCore\Classes\Locales::getLocales();
    @endphp
    @if(count($hreflangLocales) > 1)
        <link rel="alternate" hreflang="x-default"
              href="{{ $model->getUrl($hreflangLocales[0]['id'], false) }}"/>
        @foreach($hreflangLocales as $locale)
            @php
                $hreflangCode = !empty($locale['regional']) ? str_replace('_', '-', $locale['regional']) : $locale['id'];
            @endphp
            <link rel="alternate" hreflang="{{ $hreflangCode }}" href="{{ $model->getUrl($locale['id'], false) }}"/>
        @endforeach
    @endif
@endisset

<meta name="description" content="{{ seo()->metaData('metaDescription') }}">
<link rel="canonical" href="{{ request()->url() }}">
<meta property="og:url" content="{{ request()->url() }}">
<meta property="og:site_name" content="{{ $ogSiteName }}">
<meta property="og:title" content="{{ seo()->metaData('metaTitle') }}">
<meta property="og:description" content="{{ seo()->metaData('metaDescription') }}">
<meta property="og:type" content="{{ seo()->metaData('ogType') }}">
<meta property="og:locale"
      content="{{ \Dashed\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleRegional() }}">
@if(seo()->metaData('metaImage'))
    <meta property="og:image" content="{!! seo()->metaData('metaImage') !!}">
@endif
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ seo()->metaData('metaTitle') }}">

<meta name="twitter:title" content="{{ seo()->metaData('metaTitle') }}">
<meta name="twitter:description" content="{{ seo()->metaData('metaDescription') }}">
<meta name="twitter:card" content="summary_large_image">
@if(seo()->metaData('metaImage'))
    <meta name="twitter:image" content="{!! seo()->metaData('metaImage') !!}">
@endif
@if(seo()->metaData('twitterSite'))
    <meta name="twitter:site" content="{{ seo()->metaData('twitterSite') }}">
@endif
@if(seo()->metaData('twitterCreator'))
    <meta name="twitter:creator" content="{{ seo()->metaData('twitterCreator') }}">
@endif

<meta itemprop="name" content="{{ seo()->metaData('metaTitle') }}">
<meta itemprop="description" content="{{ seo()->metaData('metaDescription') }}">
@if(seo()->metaData('metaImage'))
    <meta itemprop="image" content="{!! seo()->metaData('metaImage') !!}">
@endif

<meta name="robots"
      content="{{ (app()->isLocal() || (isset($model) && $model->metaData && $model->metaData->noindex)) ? 'noindex, nofollow' : 'index, follow' }}">

@foreach(seo()->metaData('webmasterTags') as $platform => $webmasterTag)
    @if($webmasterTag)
        <meta name="{{ $platform }}-site-verification" content="{{ $webmasterTag }}"/>
    @endif
@endforeach

@foreach(seo()->metaData('schemas') as $schema)
    {!! $schema !!}
@endforeach

@stack('metadata')

{!! $slot !!}

@if($facebookEnabled)
    @if(config('dashed-core.performance.defer_third_party_scripts'))
        @php
            $fbIds = array_filter([$fbConversionId, $fbSiteId]);
            $fbInits = implode("\n", array_map(fn($id) => "fbq('init', '{$id}');", $fbIds));
            $fbScript = "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');\n" .
                $fbInits . "\n" .
                "fbq('track', 'PageView');";
            app(\Dashed\DashedCore\Performance\Scripts\DeferredScriptStore::class)->add('facebook-pixel', $fbScript);
        @endphp
    @else
        <script>
            !function (f, b, e, v, n, t, s) {
                if (f.fbq) return;
                n = f.fbq = function () {
                    n.callMethod
                        ? n.callMethod.apply(n, arguments)
                        : n.queue.push(arguments);
                };
                if (!f._fbq) f._fbq = n;
                n.push = n;
                n.loaded = !0;
                n.version = '2.0';
                n.queue = [];
                t = b.createElement(e);
                t.async = !0;
                t.src = v;
                s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s);
            }(window, document, 'script',
                'https://connect.facebook.net/en_US/fbevents.js');

            @if($fbConversionId)
            fbq('init', '{{ $fbConversionId }}');
            @endif

            @if($fbSiteId)
            fbq('init', '{{ $fbSiteId }}');
            @endif

            fbq('track', 'PageView');
        </script>
    @endif

    @if($fbConversionId)
        <noscript>
            <img height="1" width="1" style="display:none"
                 src="https://www.facebook.com/tr?id={{ $fbConversionId }}&ev=PageView&noscript=1"/>
        </noscript>
    @endif

    @if($fbSiteId)
        <noscript>
            <img height="1" width="1" style="display:none"
                 src="https://www.facebook.com/tr?id={{ $fbSiteId }}&ev=PageView&noscript=1"/>
        </noscript>
    @endif
@endif
