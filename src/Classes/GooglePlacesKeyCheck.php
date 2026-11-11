<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Facades\Mail;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Mail\NotificationMail;

/**
 * Controleert per site of de ingevulde Google Maps Places key op de nieuwe
 * Places API werkt, en mailt de beheerders één keer als dat niet zo is.
 */
class GooglePlacesKeyCheck
{
    public const SETTING_NOTIFIED = 'google_maps_places_key_notice_sent';

    /**
     * @return array<int, array{site: string, error: ?string}> de sites waarvoor gemaild is
     */
    public static function notifyOutdatedKeys(): array
    {
        $outdated = [];

        foreach (Sites::getSites() as $site) {
            $siteId = $site['id'];
            $key = (string) Customsetting::get('google_maps_places_key', $siteId);
            $placeId = (string) Customsetting::get('google_maps_places_id', $siteId);

            // Zonder key en ID draait de reviews-sync niet: niets te vervangen.
            if ($key === '' || $placeId === '') {
                continue;
            }

            $result = GooglePlaces::fromNewApi($key, $placeId);
            if ($result['ok'] || Customsetting::get(self::SETTING_NOTIFIED, $siteId) === md5($key)) {
                continue;
            }

            $outdated[] = ['site' => (string) ($site['name'] ?? $siteId), 'error' => $result['error']];
            Customsetting::set(self::SETTING_NOTIFIED, md5($key), $siteId);
        }

        if ($outdated === []) {
            return [];
        }

        $siteName = (string) Customsetting::get('site_name');
        $subject = 'Actie nodig: vernieuw de Google Maps Places key van ' . ($siteName ?: 'je website');

        $sites = implode('; ', array_map(fn (array $row) => $row['site'] . ($row['error'] ? ' (Google meldt: ' . $row['error'] . ')' : ''), $outdated));
        $content = 'De Google Maps Places key van deze website werkt niet op de nieuwe "Places API (New)" van Google. '
            . 'Google zet de oude Places API uit; zonder nieuwe key worden het cijfer en het aantal Google-reviews op de website niet meer bijgewerkt. '
            . 'Het gaat om: ' . $sites . '. '
            . 'Wat te doen: log in op het CMS, ga naar Instellingen, Algemeen en klik bij "Google Maps Places key" op "Verkrijg een key". '
            . 'Daar staan de stappen en de links naar Google Cloud. Controleer het daarna met de knop "Test reviews syncen".';

        // Geen superadmin of ingesteld adres: dan naar het algemene adres van de site.
        $recipients = SecurityAlerts::recipients() ?: array_filter([(string) Customsetting::get('site_to_email')]);

        foreach ($recipients as $email) {
            rescue(fn () => Mail::to($email)->send(new NotificationMail($content, $subject)), report: false);
        }

        return $outdated;
    }
}
