@if(env('APP_ENV') != 'local')
{{--    @if(isset($order) && $order->isPaidFor() && ((Customsetting::get('google_analytics_id') || Customsetting::get('google_tagmanager_id'))))--}}
{{--        @php($productsPurchasedLoopCount = 0)--}}
{{--        <script>--}}
{{--            window.dataLayer = window.dataLayer || [];--}}
{{--            dataLayer.push({--}}
{{--                'transactionId': '{{$order->invoice_id}}',--}}
{{--                'transactionAffiliation': '{{Customsetting::get('store_name')}}',--}}
{{--                'transactionTotal': {{ number_format($order->total, 2, '.', '') }},--}}
{{--                'transactionTax': {{ number_format($order->btw, 2, '.', '') }},--}}
{{--                'transactionShipping': {{ number_format(0, 2, '.', '') }},--}}
{{--                'transactionCurrency': 'EUR',--}}
{{--                'transactionCoupon': '{{ $order->discountCode ? $order->discountCode->code : '' }}',--}}
{{--                'transactionProducts': [--}}
{{--                    @foreach($order->orderProducts as $orderProduct)--}}
{{--                    @if($productsPurchasedLoopCount > 0)--}}
{{--                    ,--}}
{{--                        @endif--}}
{{--                    {--}}
{{--                        'sku': '{{$orderProduct->sku}}',--}}
{{--                        'name': '{{$orderProduct->name}}',--}}
{{--                        --}}{{--'item_id': '{{$orderProduct->product->id}}',--}}
{{--                        'price': {{number_format($orderProduct->price, 2, '.', '')}},--}}
{{--                        'quantity': {{$orderProduct->quantity}},--}}
{{--                    }--}}
{{--                    @php($productsPurchasedLoopCount++)--}}
{{--                    @endforeach--}}
{{--                ]--}}
{{--            });--}}
{{--        </script>--}}
{{--    @endif--}}

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
          href="{{Thumbnail::src($favicon->getUrl())->crop(57,57)->url(true)}}">
    <link rel="apple-touch-icon" sizes="60x60"
          href="{{Thumbnail::src($favicon->getUrl())->crop(60,60)->url(true)}}">
    <link rel="apple-touch-icon" sizes="72x72"
          href="{{Thumbnail::src($favicon->getUrl())->crop(72,72)->url(true)}}">
    <link rel="apple-touch-icon" sizes="76x76"
          href="{{Thumbnail::src($favicon->getUrl())->crop(76,76)->url(true)}}">
    <link rel="apple-touch-icon" sizes="114x114"
          href="{{Thumbnail::src($favicon->getUrl())->crop(114,114)->url(true)}}">
    <link rel="apple-touch-icon" sizes="120x120"
          href="{{Thumbnail::src($favicon->getUrl())->crop(120,120)->url(true)}}">
    <link rel="apple-touch-icon" sizes="144x144"
          href="{{Thumbnail::src($favicon->getUrl())->crop(144,144)->url(true)}}">
    <link rel="apple-touch-icon" sizes="152x152"
          href="{{Thumbnail::src($favicon->getUrl())->crop(152,152)->url(true)}}">
    <link rel="apple-touch-icon" sizes="180x180"
          href="{{Thumbnail::src($favicon->getUrl())->crop(180,180)->url(true)}}">
    <link rel="icon" type="image/png" sizes="192x192"
          href="{{Thumbnail::src($favicon->getUrl())->crop(192,192)->url(true)}}">
    <link rel="icon" type="image/png" sizes="32x32"
          href="{{Thumbnail::src($favicon->getUrl())->crop(32,32)->url(true)}}">
    <link rel="icon" type="image/png" sizes="96x96"
          href="{{Thumbnail::src($favicon->getUrl())->crop(96,96)->url(true)}}">
    <link rel="icon" type="image/png" sizes="16x16"
          href="{{Thumbnail::src($favicon->getUrl())->crop(16,16)->url(true)}}">
@endif

@include('cookieConsent::index')

{!! Customsetting::get('extra_scripts') !!}

{!! SEO::generate() !!}

{!! $slot !!}

@if(Customsetting::get('facebook_pixel_conversion_id') || Customsetting::get('facebook_pixel_store_id'))
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
        @if(Customsetting::get('facebook_pixel_store_id'))
        fbq('init', {{Customsetting::get('facebook_pixel_store_id')}});
        @endif
        fbq('track', 'PageView');
    </script>
    @if(Customsetting::get('facebook_pixel_conversion_id'))
        <noscript><img height="1" width="1" style="display:none"
                       src="https://www.facebook.com/tr?id={{Customsetting::get('facebook_pixel_conversion_id')}}&ev=PageView&noscript=1"
            /></noscript>
    @endif
    @if(Customsetting::get('facebook_pixel_store_id'))
        <noscript><img height="1" width="1" style="display:none"
                       src="https://www.facebook.com/tr?id={{Customsetting::get('facebook_pixel_store_id')}}&ev=PageView&noscript=1"
            /></noscript>
    @endif
@endif
