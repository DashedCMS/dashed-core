<div
    class="@if($data['top_margin'] ?? true) pt-16 sm:pt-24 @endif @if($data['bottom_margin'] ?? true) pb-16 sm:pb-24 @endif">
    <x-container :show="$data['in_container'] ?? true">
        @if(!Customsetting::get('google_maps_places_key'))
            <p>Vul een Google Maps Places key in</p>
        @endif

        <iframe
            width="100%"
            height="400"
            frameborder="0" style="border:0"
            referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps/embed/v1/place?key={{ Customsetting::get('google_maps_places_key') }}&q={{ Customsetting::get('company_name') }}"
            allowfullscreen>
        </iframe>
    </x-container>
</div>
