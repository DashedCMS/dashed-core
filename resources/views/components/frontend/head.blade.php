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
          href="{{ glide($favicon, [
    'w' => 57,
    'h' => 57,
    'q' => 100,
]) }}">
    <link rel="apple-touch-icon" sizes="60x60"
          href="{{ glide($favicon, [
    'w' => 60,
    'h' => 60,
    'q' => 100,
]) }}">
    <link rel="apple-touch-icon" sizes="72x72"
          href="{{ glide($favicon, [
    'w' => 72,
    'h' => 72,
    'q' => 100,
]) }}">
    <link rel="apple-touch-icon" sizes="76x76"
          href="{{ glide($favicon, [
    'w' => 76,
    'h' => 76,
    'q' => 100,
]) }}">
    <link rel="apple-touch-icon" sizes="114x114"
          href="{{ glide($favicon, [
    'w' => 114,
    'h' => 114,
    'q' => 100,
]) }}">
    <link rel="apple-touch-icon" sizes="120x120"
          href="{{ glide($favicon, [
    'w' => 120,
    'h' => 120,
    'q' => 100,
]) }}">
    <link rel="apple-touch-icon" sizes="144x144"
          href="{{ glide($favicon, [
    'w' => 144,
    'h' => 144,
    'q' => 100,
]) }}">
    <link rel="apple-touch-icon" sizes="152x152"
          href="{{ glide($favicon, [
    'w' => 152,
    'h' => 152,
    'q' => 100,
]) }}">
    <link rel="apple-touch-icon" sizes="180x180"
          href="{{ glide($favicon, [
    'w' => 180,
    'h' => 180,
    'q' => 100,
]) }}">
    <link rel="icon" type="image/png" sizes="192x192"
          href="{{ glide($favicon, [
    'w' => 192,
    'h' => 192,
    'q' => 100,
]) }}">
    <link rel="icon" type="image/png" sizes="32x32"
          href="{{ glide($favicon, [
    'w' => 32,
    'h' => 32,
    'q' => 100,
]) }}">
    <link rel="icon" type="image/png" sizes="96x96"
          href="{{ glide($favicon, [
    'w' => 96,
    'h' => 96,
    'q' => 100,
]) }}">
    <link rel="icon" type="image/png" sizes="16x16"
          href="{{ glide($favicon, [
    'w' => 16,
    'h' => 16,
    'q' => 100,
]) }}">
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
