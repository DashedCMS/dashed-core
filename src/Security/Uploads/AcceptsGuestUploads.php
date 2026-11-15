<?php

namespace Dashed\DashedCore\Security\Uploads;

/**
 * Een Livewire-component dat bezoekers zonder paneelrechten (gasten, klanten)
 * bestanden laat uploaden. De uploadpoort (UploadGate) geeft alleen deze
 * componenten een upload-URL, en alleen voor property's die ze goedkeuren.
 *
 * Een project zonder eigen code kan een component ook aanmelden via
 * config('dashed-core.uploads.guest_components').
 */
interface AcceptsGuestUploads
{
    /**
     * @param  string  $property  zoals Livewire hem doorgeeft, bv. "values.bijlage-12" of "photos"
     */
    public function acceptsGuestUpload(string $property): bool;
}
