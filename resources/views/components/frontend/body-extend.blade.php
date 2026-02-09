<x-impersonate::banner/>

@php
    $tracking = $trackingSettings ?? [];

    $googleTagmanagerId = $tracking['google_tagmanager_id'] ?? null;

    $facebookEnabled = !empty($tracking['facebook_pixel_conversion_id'] ?? null)
        || !empty($tracking['facebook_pixel_site_id'] ?? null)
        || !empty($tracking['trigger_facebook_events'] ?? false);

    $extraBody = $extraBodyScripts ?? '';
@endphp

@if(app()->isProduction() && $googleTagmanagerId)
    <noscript>
        <iframe
            src="https://www.googletagmanager.com/ns.html?id={{ $googleTagmanagerId }}"
            height="0"
            width="0"
            style="display:none;visibility:hidden"
        ></iframe>
    </noscript>
@endif

{!! $extraBody !!}

@if(isset($model))
    {!! $model->metaData->top_body_scripts ?? '' !!}
@endif

<script>
    document.addEventListener('livewire:init', () => {
        const tracking = {
            facebook: @json($facebookEnabled),
        };

        Livewire.on('formSubmitted', (event) => {
            const payload = event[0];

            if (tracking.facebook && typeof fbq !== 'undefined') {
                setTimeout(() => {
                    fbq('track', 'Contact');
                }, 1000);
            }

            if (typeof dataLayer !== 'undefined') {
                dataLayer.push({
                    event: 'formSubmit',
                    formId: payload.formId,
                    formName: payload.formName,
                });
            }
        });

        Livewire.on('searchInitiated', () => {
            if (tracking.facebook && typeof fbq !== 'undefined') {
                setTimeout(() => {
                    fbq('track', 'Search');
                }, 1000);
            }
        });
    });
</script>

{{--@include('cookie-consent::index')--}}
