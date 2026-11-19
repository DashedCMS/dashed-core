<?php

declare(strict_types=1);

namespace Dashed\DashedCore\PersonalData;

/**
 * De vaste vervangwaarden bij anonimiseren. Overal dezelfde, zodat een
 * geanonimiseerde rij in elke tabel herkenbaar is en niemand per pakket iets
 * eigens verzint dat toch nog naar een persoon wijst.
 */
final class Anonymized
{
    public const NAME = 'Geanonimiseerd';

    public const TEXT = '[verwijderd op verzoek]';

    public const DOMAIN = 'anoniem.invalid';

    /**
     * Een adres dat nooit bezorgd kan worden (.invalid is gereserveerd). Met
     * een seed (bijvoorbeeld het id van de rij) wordt het uniek, voor kolommen
     * met een unieke index.
     */
    public static function email(int|string|null $seed = null): string
    {
        return $seed === null || $seed === ''
            ? 'geanonimiseerd@' . self::DOMAIN
            : 'geanonimiseerd-' . substr(hash('sha256', (string) $seed . microtime()), 0, 12) . '@' . self::DOMAIN;
    }
}
