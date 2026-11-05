<?php

namespace Dashed\DashedCore\Classes\ErrorPages;

/**
 * Titel en uitleg per HTTP-statuscode voor de foutpagina's. Eén plek, zodat de
 * losse pagina (zonder database) en de pagina in de huisstijl hetzelfde zeggen;
 * de themapagina laat een beheerder de teksten daarbovenop nog aanpassen via
 * Translation::get() met deze waarden als terugval.
 */
class ErrorPageTexts
{
    public static function title(int $status): string
    {
        return match ($status) {
            400 => __('Ongeldig verzoek'),
            401 => __('Niet ingelogd'),
            403 => __('Geen toegang'),
            404 => __('Pagina niet gevonden'),
            405 => __('Niet toegestaan'),
            419 => __('Pagina verlopen'),
            429 => __('Te veel verzoeken'),
            503 => __('Even geduld'),
            default => __('Er ging iets mis'),
        };
    }

    public static function message(int $status): string
    {
        return match ($status) {
            400 => __('Het verzoek kon niet worden verwerkt. Ga terug en probeer het opnieuw.'),
            401 => __('Je moet ingelogd zijn om deze pagina te bekijken.'),
            403 => __('Je hebt geen toegang tot deze pagina.'),
            404 => __('De pagina waar je naar op zoek bent bestaat niet (meer).'),
            405 => __('Deze actie is op deze pagina niet toegestaan.'),
            419 => __('Je sessie is verlopen. Ga terug en probeer het opnieuw.'),
            429 => __('Je hebt in korte tijd te veel verzoeken gedaan. Wacht even en probeer het opnieuw.'),
            503 => __('De website is tijdelijk niet beschikbaar wegens onderhoud. Probeer het over een paar minuten opnieuw.'),
            default => __('Er is een fout opgetreden aan onze kant. We zijn op de hoogte en werken aan een oplossing. Probeer het later opnieuw.'),
        };
    }
}
