@if(env('APP_ENV') != 'local')
    @if(Customsetting::get('google_tagmanager_id'))
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id={{Customsetting::get('google_tagmanager_id')}}"
                    height="0" width="0" style="display:none;visibility:hidden"></iframe>
        </noscript>
    @endif
@endif

{{--@if(isset($product))--}}
{{--    <x-qcommerce::frontend.products.schema :product="$product"></x-qcommerce::frontend.products.schema>--}}
{{--@endif--}}
{{--@if(isset($products))--}}
{{--    @foreach($products as $product)--}}
{{--        <x-qcommerce::frontend.products.schema :product="$product"></x-qcommerce::frontend.products.schema>--}}
{{--    @endforeach--}}
{{--@endif--}}

{{--@if(isset($order) && $order->isPaidFor() && (Customsetting::get('facebook_pixel_conversion_id') || Customsetting::get('facebook_pixel_store_id')))--}}
{{--    <script>--}}
{{--        fbq('track', 'Purchase', {currency: "EUR", value: {{number_format($order->total, 2, '.', '')}} });--}}
{{--    </script>--}}
{{--@endif--}}
