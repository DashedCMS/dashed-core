@php(cms()->checkModelPassword($model ?? null))

@if(is_numeric(seo()->metaData('metaImage')))
    @php(seo()->metaData('metaImage', mediaHelper()->getSingleMedia(seo()->metaData('metaImage'), 'original')->url ?? ''))
@endif

@if(env('APP_ENV') != 'local')
    @if(Customsetting::get('google_tagmanager_id'))
        <script>
            (function (w, d, s, l, i) {
                w[l] = w[l] || [];
                w[l].push({
                    'gtm.start':
                        new Date().getTime(), event: 'gtm.js'
                });
                var f = d.getElementsByTagName(s)[0],
                    j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
                j.async = true;
                j.src =
                    'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', '{{Customsetting::get('google_tagmanager_id')}}');
        </script>
    @endif

    @if(Customsetting::get('google_analytics_id'))
        <script async
                src="https://www.googletagmanager.com/gtag/js?id={{Customsetting::get('google_analytics_id')}}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());

            gtag('config', '{{Customsetting::get('google_analytics_id')}}');
        </script>
    @endif
@endif

{!! Customsetting::get('extra_scripts') !!}

@if($favicon)
    <link rel="apple-touch-icon" sizes="57x57"
          href="{{ $favicon->url ?? false }}">
    <link rel="apple-touch-icon" sizes="60x60"
          href="{{ $favicon->url ?? false }}">
    <link rel="apple-touch-icon" sizes="72x72"
          href="{{ $favicon->url ?? false }}">
    <link rel="apple-touch-icon" sizes="76x76"
          href="{{ $favicon->url ?? false }}">
    <link rel="apple-touch-icon" sizes="114x114"
          href="{{ $favicon->url ?? false }}">
    <link rel="apple-touch-icon" sizes="120x120"
          href="{{ $favicon->url ?? false }}">
    <link rel="apple-touch-icon" sizes="144x144"
          href="{{ $favicon->url ?? false }}">
    <link rel="apple-touch-icon" sizes="152x152"
          href="{{ $favicon->url ?? false }}">
    <link rel="apple-touch-icon" sizes="180x180"
          href="{{ $favicon->url ?? false }}">
    <link rel="icon" type="image/png" sizes="192x192"
          href="{{ $favicon->url ?? false }}">
    <link rel="icon" type="image/png" sizes="32x32"
          href="{{ $favicon->url ?? false }}">
    <link rel="icon" type="image/png" sizes="96x96"
          href="{{ $favicon->url ?? false }}">
    <link rel="icon" type="image/png" sizes="16x16"
          href="{{ $favicon->url ?? false }}">
@endif

@if(isset($model))
    {!! $model->metaData->head_scripts ?? '' !!}
@endif

<title>{{ seo()->metaData('metaTitle') }}</title>
@isset($model)
    {{--    @foreach(seo()->metaData('alternateUrls') as $locale => $url)--}}
    {{--        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}"/>--}}
    {{--    @endforeach--}}
    <link rel="alternate" hreflang="x-default"
          href="{{ $model->getUrl(\Dashed\DashedCore\Classes\Locales::getFirstLocale()['id'], false) }}"/>
    @foreach(\Dashed\DashedCore\Classes\Locales::getLocales() as $locale)
        <link rel="alternate" hreflang="{{ $locale['id'] }}" href="{{ $model->getUrl($locale['id'], false) }}"/>
    @endforeach
@endisset
<meta name="description" content="{{ seo()->metaData('metaDescription') }}">
<link rel="canonical" href="{{ request()->fullUrl()}}">
<meta property="og:url" content="{{ request()->url() }}">
<meta property="og:site_name" content="{{ Customsetting::get('site_name') }}">
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
      content="{{ (env('APP_ENV') == 'local' || (isset($model) && $model->metaData && $model->metaData->noindex)) ? 'noindex, nofollow' : 'index, follow' }}">

@foreach(seo()->metaData('webmasterTags') as $platform => $webmasterTag)
    @if($webmasterTag)
        <meta name="{{$platform}}-site-verification" content="{{ $webmasterTag }}"/>
    @endif
@endforeach

@foreach(seo()->metaData('schemas') as $schema)
    {!! $schema !!}
@endforeach

@stack('metadata')

{!! $slot !!}

@if(Customsetting::get('facebook_pixel_conversion_id') || Customsetting::get('facebook_pixel_site_id'))
    <script>
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function () {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
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
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        @if(Customsetting::get('facebook_pixel_conversion_id'))
        fbq('init', '{{Customsetting::get('facebook_pixel_conversion_id')}}');
        @endif
        @if(Customsetting::get('facebook_pixel_site_id'))
        fbq('init', '{{Customsetting::get('facebook_pixel_site_id')}}');
        @endif
        fbq('track', 'PageView');
    </script>
    @if(Customsetting::get('facebook_pixel_conversion_id'))
        <noscript><img height="1" width="1" style="display:none"
                       src="https://www.facebook.com/tr?id={{Customsetting::get('facebook_pixel_conversion_id')}}&ev=PageView&noscript=1"
            /></noscript>
    @endif
    @if(Customsetting::get('facebook_pixel_site_id'))
        <noscript><img height="1" width="1" style="display:none"
                       src="https://www.facebook.com/tr?id={{Customsetting::get('facebook_pixel_site_id')}}&ev=PageView&noscript=1"
            /></noscript>
    @endif
@endif
