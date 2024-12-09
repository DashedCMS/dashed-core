@if(!Customsetting::get('google_maps_places_key'))
    <p>Vul een Google Maps Places key in</p>
@endif

<iframe
    width="100%"
    height="400"
    frameborder="0" style="border:0"
    referrerpolicy="no-referrer-when-downgrade"
    src="https://www.google.com/maps/embed/v1/place?key={{ Customsetting::get('google_maps_places_key') }}&q={{ Customsetting::get('site_name') }}"
    allowfullscreen>
</iframe>
