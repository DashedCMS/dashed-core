<?php
cms()->checkModelPassword($model ?? null);

$metaImage = \Dashed\DashedCore\Classes\Seo\MetaImage::resolve(seo()->metaData('metaImage'));
$metaDescription = \Dashed\DashedCore\Classes\Seo\MetaDescription::for((string) seo()->metaData('metaDescription'), $model ?? null, (string) ($defaultMetaDescription ?? ''));
?><?php
$tracking = $trackingSettings ?? [];

$gtmId = $tracking['google_tagmanager_id'] ?? null;
$gaId = $tracking['google_analytics_id'] ?? null;

$fbConversionId = $tracking['facebook_pixel_conversion_id'] ?? null;
$fbSiteId = $tracking['facebook_pixel_site_id'] ?? null;
$facebookEnabled = !empty($fbConversionId) || !empty($fbSiteId);

$extraScripts = $extraHeadScripts ?? '';
$scriptsDelayed = (bool) ($tracking['scripts_delayed'] ?? false);

if ($scriptsDelayed) {
    $extraScripts = \Dashed\DashedCore\Classes\DelayedScripts::rewrite($extraScripts);
}
$ogSiteName = $siteName ?? config('app.name');
?>


@if($scriptsDelayed)
    {{-- Trackingscripts laden na de eerste interactie of 3 s na load (instelling "Trackingscripts uitstellen"). --}}
    {!! \Dashed\DashedCore\Classes\DelayedScripts::loader() !!}
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
                @if($scriptsDelayed)
                (window.dashedDelayedLoaders = window.dashedDelayedLoaders || []).push(function () { f.parentNode.insertBefore(j, f); });
                @else
                f.parentNode.insertBefore(j, f);
                @endif
            })(window, document, 'script', 'dataLayer', '{{ $gtmId }}');
        </script>
    @endif

    @if($gaId)
        <!-- Google tag (gtag.js) -->
        <script @if($scriptsDelayed) type="text/plain" data-dashed-delayed @endif async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $gaId }}');
        </script>
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

<title>{{ \Dashed\DashedCore\Classes\Seo\PageTitle::withSuffix((string) seo()->metaData('metaTitle'), (string) ($metaTitleSuffix ?? ''), (string) ($siteName ?? '')) }}</title>

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

@php
    $headModel = $model ?? null;
    $canonical = seo()->metaData('canonical') ?: \Dashed\DashedCore\Classes\Seo\CanonicalUrl::for(request(), $headModel);
    $robots = \Dashed\DashedCore\Classes\Seo\RobotsDecision::for(request(), $headModel, (string) \Dashed\DashedCore\Classes\Sites::getActive());
@endphp
@if($metaDescription !== '')
<meta name="description" content="{{ $metaDescription }}">
@endif
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:site_name" content="{{ $ogSiteName }}">
<meta property="og:title" content="{{ seo()->metaData('metaTitle') }}">
@if($metaDescription !== '')
<meta property="og:description" content="{{ $metaDescription }}">
@endif
<meta property="og:type" content="{{ seo()->metaData('ogType') }}">
<meta property="og:locale"
      content="{{ \Dashed\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleRegional() }}">
@if($metaImage['url'])
    <meta property="og:image" content="{!! $metaImage['url'] !!}">
    @if($metaImage['width'] && $metaImage['height'])
        <meta property="og:image:width" content="{{ $metaImage['width'] }}">
        <meta property="og:image:height" content="{{ $metaImage['height'] }}">
    @endif
    <meta property="og:image:alt" content="{{ seo()->metaData('metaTitle') }}">
@endif

<meta name="twitter:title" content="{{ seo()->metaData('metaTitle') }}">
@if($metaDescription !== '')
<meta name="twitter:description" content="{{ $metaDescription }}">
@endif
<meta name="twitter:card" content="summary_large_image">
@if($metaImage['url'])
    <meta name="twitter:image" content="{!! $metaImage['url'] !!}">
@endif
@if(seo()->metaData('twitterSite'))
    <meta name="twitter:site" content="{{ seo()->metaData('twitterSite') }}">
@endif
@if(seo()->metaData('twitterCreator'))
    <meta name="twitter:creator" content="{{ seo()->metaData('twitterCreator') }}">
@endif


<meta name="robots" content="{{ $robots }}">

@foreach(seo()->metaData('webmasterTags') as $platform => $webmasterTag)
    @if($webmasterTag)
        <meta name="{{ $platform }}-site-verification" content="{{ $webmasterTag }}"/>
    @endif
@endforeach

@foreach(\Dashed\DashedCore\Classes\Seo\ModelSchemas::for($headModel ?? null, (array) seo()->metaData('schemas')) as $schema)
    {!! $schema !!}
@endforeach

{{-- Custom structured data (CustomStructuredData records pushed via seo()->metaData('customStructuredData', [...])) --}}
@foreach((array) seo()->metaData('customStructuredData') as $__jsonLd)
    @if(is_string($__jsonLd) && trim($__jsonLd) !== '')
        <script type="application/ld+json">{!! $__jsonLd !!}</script>
    @endif
@endforeach

@stack('metadata')

{!! $slot !!}

@if($facebookEnabled)
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
            @if($scriptsDelayed)
            (window.dashedDelayedLoaders = window.dashedDelayedLoaders || []).push(function () { s.parentNode.insertBefore(t, s); });
            @else
            s.parentNode.insertBefore(t, s);
            @endif
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
