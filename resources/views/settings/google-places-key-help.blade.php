<?php $places = \Dashed\DashedCore\Classes\GooglePlaces::class; ?>
<div style="font-size: 0.9rem; line-height: 1.6;">
    <p style="margin-bottom: 0.75rem;">
        Met deze key haalt het CMS het cijfer en het aantal reviews van je bedrijf op bij Google.
        Google heeft de oude "Places API" vervangen door <strong>"Places API (New)"</strong>:
        een key die alleen de oude API mag gebruiken, moet vervangen worden.
    </p>

    <ol style="list-style: decimal; padding-left: 1.25rem; display: grid; gap: 0.6rem;">
        <li>
            Open de <a href="{{ $places::CONSOLE_LIBRARY_URL }}" target="_blank" rel="noopener" style="text-decoration: underline;">Places API (New) in Google Cloud</a>,
            kies bovenaan het project van deze website (of maak er een aan) en klik op <strong>Enable</strong>.
        </li>
        <li>
            Google vraagt om een betaalaccount bij het project. Zonder
            <a href="{{ $places::CONSOLE_BILLING_URL }}" target="_blank" rel="noopener" style="text-decoration: underline;">billing</a>
            weigert de API elk verzoek. Het ophalen van reviews valt ruim binnen het gratis maandtegoed.
        </li>
        <li>
            Ga naar <a href="{{ $places::CONSOLE_CREDENTIALS_URL }}" target="_blank" rel="noopener" style="text-decoration: underline;">Credentials</a>,
            klik op <strong>Create credentials</strong> en kies <strong>API key</strong>.
        </li>
        <li>
            Kies bij <strong>API restrictions</strong> alleen <strong>Places API (New)</strong>.
            Laat <strong>Application restrictions</strong> op <strong>None</strong> staan (of kies "IP addresses" met het IP-adres van de server):
            met "Websites" werkt de key niet, omdat de server het verzoek doet en niet de browser.
            Toon je ook de Google Maps-kaart (blok "Maps embed"), vink dan ook <strong>Maps Embed API</strong> aan.
        </li>
        <li>
            Kopieer de key, plak hem hier in het veld <strong>Google Maps Places key</strong> en sla op.
        </li>
        <li>
            Vul ook het <strong>Google Maps Places ID</strong> van je bedrijf in. Die vind je met de
            <a href="{{ $places::PLACE_ID_FINDER_URL }}" target="_blank" rel="noopener" style="text-decoration: underline;">Place ID Finder</a>.
        </li>
        <li>
            Klik op <strong>Test reviews syncen</strong> om te controleren of het werkt.
        </li>
    </ol>

    <p style="margin-top: 0.75rem;">
        Meer uitleg van Google: <a href="{{ $places::DOCS_URL }}" target="_blank" rel="noopener" style="text-decoration: underline;">een API key aanmaken</a>.
    </p>
</div>
