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

@if($favicon)
    <link rel="apple-touch-icon" sizes="57x57"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', str_replace('/qcommerce', 'qcommerce', $favicon), [
                        'widen' => 57,
                        'heighten' => 57
                    ]) }}">
    <link rel="apple-touch-icon" sizes="60x60"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 60,
                        'heighten' => 60
                    ]) }}">
    <link rel="apple-touch-icon" sizes="72x72"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 72,
                        'heighten' => 72
                    ]) }}">
    <link rel="apple-touch-icon" sizes="76x76"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 76,
                        'heighten' => 76
                    ]) }}">
    <link rel="apple-touch-icon" sizes="114x114"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 114,
                        'heighten' => 114
                    ]) }}">
    <link rel="apple-touch-icon" sizes="120x120"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 120,
                        'heighten' => 120
                    ]) }}">
    <link rel="apple-touch-icon" sizes="144x144"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 144,
                        'heighten' => 144
                    ]) }}">
    <link rel="apple-touch-icon" sizes="152x152"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 152,
                        'heighten' => 152
                    ]) }}">
    <link rel="apple-touch-icon" sizes="180x180"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 180,
                        'heighten' => 180
                    ]) }}">
    <link rel="icon" type="image/png" sizes="192x192"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 192,
                        'heighten' => 192
                    ]) }}">
    <link rel="icon" type="image/png" sizes="32x32"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 32,
                        'heighten' => 32
                    ]) }}">
    <link rel="icon" type="image/png" sizes="96x96"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 96,
                        'heighten' => 96
                    ]) }}">
    <link rel="icon" type="image/png" sizes="16x16"
          href="{{ app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', $favicon, [
                        'widen' => 16,
                        'heighten' => 16
                    ]) }}">
@endif

{!! Customsetting::get('extra_scripts') !!}

<title>{{ seo()->metaData('metaTitle') }}</title>
@foreach(seo()->metaData('alternateUrls') as $locale => $url)
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}"/>
@endforeach
<meta name="description" content="{{ seo()->metaData('metaDescription') }}">
<link rel="canonical" href="{{ request()->url() }}">
<meta property="og:url" content="{{ request()->url() }}">
<meta property="og:site_name" content="{{ Customsetting::get('site_name', 'Quezy') }}">
<meta property="og:title" content="{{ seo()->metaData('metaTitle') }}">
<meta property="og:description" content="{{ seo()->metaData('metaDescription') }}">
<meta property="og:type" content="{{ seo()->metaData('ogType') }}">
<meta property="og:locale"
      content="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleRegional() }}">
    @dump(seo()->metaData('metaImage'))
@if(seo()->metaData('metaImage'))
    <meta property="og:image" content="{!! seo()->metaData('metaImage') !!}">
@else
    <meta property="og:image"
          content="{!! app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', Customsetting::get('default_meta_data_image'), [
                        'fit' => [1200, 630],
]) !!}">
@endif
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ seo()->metaData('metaTitle') }}">
<meta name="twitter:title" content="{{ seo()->metaData('metaTitle') }}">
<meta name="twitter:description" content="{{ seo()->metaData('metaDescription') }}">
<meta name="twitter:card" content="summary_large_image">
@if(seo()->metaData('metaImage'))
    <meta name="twitter:image" content="{!! seo()->metaData('metaImage') !!}">
@else
    <meta name="twitter:image"
          content="{!! app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', Customsetting::get('default_meta_data_image'), []) !!}">
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
@else
    <meta itemprop="image"
          content="{!! app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', Customsetting::get('default_meta_data_image'), []) !!}">
@endif
<meta name="robots" content="{{ seo()->metaData('robots') }}">

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
        fbq('init', {{Customsetting::get('facebook_pixel_conversion_id')}});
        @endif
        @if(Customsetting::get('facebook_pixel_site_id'))
        fbq('init', {{Customsetting::get('facebook_pixel_site_id')}});
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
