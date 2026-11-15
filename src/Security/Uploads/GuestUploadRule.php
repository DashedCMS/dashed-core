<?php

namespace Dashed\DashedCore\Security\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

/**
 * Benoemde regel 'dashed_guest_upload' die ApplyUploadPolicy aan de tijdelijke
 * Livewire-upload hangt voor bezoekers zonder paneelrechten:
 *  - svg/html/xml/js/uitvoerbaar (naam of inhoud) altijd weigeren;
 *  - buiten de typelijst: weigeren in modus enforce, loggen in modus log.
 * De extensie komt zowel uit de naam van de bezoeker als uit de inhoud.
 */
class GuestUploadRule
{
    public const RULE = 'dashed_guest_upload';

    public static function register(): void
    {
        Validator::extend(static::RULE, function (string $attribute, mixed $value, array $parameters, ValidatorContract $validator): bool {
            $reason = $value instanceof UploadedFile ? static::rejectionReason($value) : __('Ongeldig bestand.');

            if ($reason !== null) {
                $validator->setCustomMessages([$attribute . '.' . static::RULE => $reason]);
            }

            return $reason === null;
        }, __('Dit bestandstype is niet toegestaan.'));
    }

    /**
     * Inhoud die een browser als document met script kan tonen. Niet op "xml"
     * in het algemeen: docx/xlsx heten application/vnd.openxmlformats-....
     */
    protected static function isScriptableMime(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/svg')
            || in_array($mimeType, ['text/html', 'application/xhtml+xml', 'text/xml', 'application/xml', 'text/javascript', 'application/javascript', 'application/x-javascript'], true);
    }

    public static function rejectionReason(UploadedFile $file): ?string
    {
        $clientExtension = strtolower((string) pathinfo((string) $file->getClientOriginalName(), PATHINFO_EXTENSION));
        $guessedExtension = strtolower((string) $file->guessExtension());
        $mimeType = strtolower((string) $file->getMimeType());

        $blocked = UploadPolicy::blockedGuestExtensions();
        if (in_array($clientExtension, $blocked, true) || in_array($guessedExtension, $blocked, true) || static::isScriptableMime($mimeType)) {
            return __('Dit bestandstype is niet toegestaan.');
        }

        $allowed = UploadPolicy::guestMimes();
        $guessedForList = $guessedExtension === 'jpeg' ? 'jpg' : $guessedExtension;
        $inList = $allowed === []
            || in_array($guessedExtension, $allowed, true)
            || in_array($guessedForList, $allowed, true)
            // finfo kent heic/avif niet altijd; dan telt de naam, mits de inhoud geen ander bekend type is.
            || (in_array($clientExtension, $allowed, true) && in_array($guessedExtension, ['', 'bin'], true));

        if ($inList) {
            return null;
        }

        Log::warning('[dashed-uploads] gastupload buiten de typelijst', [
            'mode' => UploadPolicy::mode(),
            'client_extension' => mb_substr($clientExtension, 0, 20),
            'guessed_extension' => $guessedExtension,
            'mime' => $mimeType,
            'ip' => request()->ip(),
        ]);

        return UploadPolicy::enforcing() ? __('Dit bestandstype is niet toegestaan.') : null;
    }
}
