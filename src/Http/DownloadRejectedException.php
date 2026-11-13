<?php

namespace Dashed\DashedCore\Http;

use RuntimeException;

/**
 * De melding is bedoeld voor een beheerder en bevat daarom nooit de ruwe
 * foutmelding van curl of de inhoud van het antwoord.
 */
final class DownloadRejectedException extends RuntimeException
{
}
