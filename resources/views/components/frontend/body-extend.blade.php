@if(env('APP_ENV') != 'local')
    @if(Customsetting::get('google_tagmanager_id'))
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id={{Customsetting::get('google_tagmanager_id')}}"
                    height="0" width="0" style="display:none;visibility:hidden"></iframe>
        </noscript>
    @endif
@endif

{!! Customsetting::get('extra_body_scripts') !!}

@if(isset($model))
    {!! $model->metaData->top_body_scripts ?? '' !!}
@endif

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('formSubmitted', (event) => {
            @if(Customsetting::get('facebook_pixel_conversion_id') || Customsetting::get('facebook_pixel_site_id'))
            fbq('track', 'Contact');
            @endif
        });

        Livewire.on('searchInitiated', (event) => {
            @if(Customsetting::get('facebook_pixel_conversion_id') || Customsetting::get('facebook_pixel_site_id'))
            fbq('track', 'Search');
            @endif
        });
    });
</script>

@include('cookie-consent::index')
