<?php

namespace Dashed\DashedCore\Security\Uploads;

use Illuminate\Support\Facades\Log;

/**
 * Livewire geeft een gesigneerde upload-URL aan elke bezoeker die
 * `_startUpload` aanroept op een component met WithFileUploads, ongeacht of
 * dat component voor die property een bestandsveld heeft. De URL is niet aan
 * het component gebonden. Deze poort (via Livewire's 'call'-hook, zie
 * UploadSecurity) laat `_startUpload`/`_finishUpload` voor bezoekers zonder
 * paneelrechten alleen toe bij een component dat AcceptsGuestUploads
 * implementeert en de property goedkeurt, of dat in
 * dashed-core.uploads.guest_components staat.
 *
 * In modus "log" wordt alleen gelogd, zodat per project zichtbaar wordt welke
 * componenten nog aangemeld moeten worden voordat "enforce" aan kan.
 */
class UploadGate
{
    public const GUARDED_METHODS = ['_startUpload', '_finishUpload'];

    public static function check(object $component, string $method, array $params): void
    {
        if (! in_array($method, static::GUARDED_METHODS, true) || ! UploadPolicy::active()) {
            return;
        }

        if (UploadPolicy::isPanelUser(auth()->user())) {
            return;
        }

        $property = (string) ($params[0] ?? '');

        if (static::allows($component, $property)) {
            return;
        }

        Log::warning('[dashed-uploads] gastupload naar een component dat niet is aangemeld', [
            'mode' => UploadPolicy::mode(),
            'component' => $component::class,
            'method' => $method,
            'property' => mb_substr($property, 0, 100),
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
            'url' => mb_substr((string) request()->header('referer'), 0, 200),
        ]);

        if (UploadPolicy::enforcing()) {
            abort(403, __('Uploaden is hier niet toegestaan.'));
        }
    }

    public static function allows(object $component, string $property): bool
    {
        // Ook zonder de interface: pakketten (forms, ecommerce-core, livechat)
        // hebben alleen de methode, zodat ze niet aan deze core-versie vastzitten.
        if ($component instanceof AcceptsGuestUploads || method_exists($component, 'acceptsGuestUpload')) {
            return (bool) $component->acceptsGuestUpload($property);
        }

        foreach (UploadPolicy::guestComponents() as $class) {
            if ($component instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
