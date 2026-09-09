<?php

namespace Dashed\DashedCore\Security;

/**
 * De beveiligingsmaatregelen van het CMS, in leesbare vorm, voor de uitleg op
 * de pagina Beveiliging. Per maatregel: wat hij doet en waar hij ingesteld
 * wordt (op deze pagina, elders in het CMS, op de server, of automatisch).
 * De lijst beschrijft; wat er echt aan of uit staat toont de Beveiligingscheck.
 */
class SecurityMeasures
{
    public const HERE = 'here';

    public const CMS = 'cms';

    public const SERVER = 'server';

    public const AUTOMATIC = 'automatic';

    /**
     * @return array<string, string>
     */
    public static function whereLabels(): array
    {
        return [
            self::HERE => __('Op deze pagina'),
            self::CMS => __('Elders in het CMS'),
            self::SERVER => __('Server (.env)'),
            self::AUTOMATIC => __('Automatisch'),
        ];
    }

    /**
     * @return array<int, array{title: string, body: string, where: string, location: string}>
     */
    public static function all(): array
    {
        return [
            [
                'title' => __('Toegang op IP-adres'),
                'body' => __('Staat er een lijst met adressen, dan is het CMS (en Horizon) alleen nog vanaf die adressen bereikbaar; elk ander adres krijgt een foutmelding, ook op de inlogpagina. Leeg betekent geen beperking. Elke wijziging van de lijst gaat per mail naar alle superadmins. Buitengesloten? Op de server: php artisan dashed:cms-ip-allowlist --clear.'),
                'where' => self::HERE,
                'location' => __('Sectie "Toegang tot het CMS op IP-adres"'),
            ],
            [
                'title' => __('Automatisch uitloggen'),
                'body' => __('Een beheerder die het CMS een tijd niet gebruikt wordt bij zijn volgende actie uitgelogd en ziet op het inlogscherm waarom. Een open dashboard dat alleen zichzelf ververst telt niet als gebruik.'),
                'where' => self::HERE,
                'location' => __('Sectie "Automatisch uitloggen"'),
            ],
            [
                'title' => __('Beveiligingsmeldingen'),
                'body' => __('Een e-mail zodra een beheerder inlogt vanaf een IP-adres dat voor dat account nieuw is, en bij een mislukte inlogpoging op een beheerdersaccount (hooguit een per kwartier per account). Alles staat daarnaast in het inloglogboek.'),
                'where' => self::HERE,
                'location' => __('Sectie "Beveiligingsmeldingen"'),
            ],
            [
                'title' => __('Verzoeklimieten'),
                'body' => __('Per IP-adres per minuut op de website: bestelpagina\'s en downloads op orderhash, winkelwagen, kortingscode, inloggen en registreren. Wie erboven komt krijgt een 429 en moet even wachten.'),
                'where' => self::HERE,
                'location' => __('Sectie "Verzoeklimieten"'),
            ],
            [
                'title' => __('Tweestapsverificatie (MFA) verplicht'),
                'body' => __('Iedere beheerder moet MFA instellen via een authenticator-app; wie het nog niet heeft krijgt bij de volgende paginalading de instelpagina. Herstelcodes vang je op bij het instellen. Een superadmin kan de MFA van een collega resetten bij Gebruikers.'),
                'where' => self::AUTOMATIC,
                'location' => __('Altijd aan; e-mail als extra methode bij Instellingen, Account'),
            ],
            [
                'title' => __('MFA opnieuw bevestigen'),
                'body' => __('Na een instelbaar aantal uur (standaard 24) vraagt het CMS opnieuw een code, ook midden in een sessie. Optioneel ook zodra het IP-adres van de beheerder verandert.'),
                'where' => self::CMS,
                'location' => __('Instellingen, Account, sectie "MFA opnieuw bevestigen"'),
            ],
            [
                'title' => __('Inloglogboek'),
                'body' => __('Elke inlogpoging op het CMS wordt vastgelegd: gelukt, mislukt, mislukt op MFA-code, uitgelogd, automatisch uitgelogd en geweigerd op IP. Standaard 180 dagen bewaard.'),
                'where' => self::CMS,
                'location' => __('Gebruikers, Inlogpogingen; bewaartermijn bij Instellingen, Opschonen'),
            ],
            [
                'title' => __('Wachtwoordeisen'),
                'body' => __('Overal waar een wachtwoord gekozen wordt (website en CMS) gelden minimaal 8 tekens en, in productie, een controle tegen bekende datalekken. Inloggen met een bestaand korter wachtwoord blijft werken. Wachtwoord-resetlinks zijn 60 minuten geldig.'),
                'where' => self::AUTOMATIC,
                'location' => __('DASHED_PASSWORD_MIN_LENGTH en DASHED_PASSWORD_UNCOMPROMISED om af te wijken'),
            ],
            [
                'title' => __('Facturen en pakbonnen afgeschermd'),
                'body' => __('De PDF\'s staan prive op de opslag en zijn alleen via de downloadlink met de orderhash te openen, die niet te raden is en onder de verzoeklimiet valt. Klanten zonder account kunnen de factuur uit hun bevestigingsmail dus gewoon blijven openen.'),
                'where' => self::AUTOMATIC,
                'location' => __('DASHED_INVOICE_REQUIRE_SIGNATURE=true eist ook de handtekening in de link'),
            ],
            [
                'title' => __('Beveiligingsheaders'),
                'body' => __('Elke pagina krijgt HSTS (alleen over https), X-Frame-Options, X-Content-Type-Options, Referrer-Policy en Permissions-Policy mee. Daarmee kan de site niet in een iframe elders geladen worden en lekken URL\'s met orderhashes niet naar andere sites.'),
                'where' => self::AUTOMATIC,
                'location' => __('DASHED_SECURITY_HEADERS en DASHED_X_FRAME_OPTIONS en verwanten om af te wijken'),
            ],
            [
                'title' => __('Vertrouwde hosts'),
                'body' => __('De site accepteert alleen verzoeken voor de eigen domeinnamen (APP_URL en de site-URL per site). Zonder deze controle zou wie een wachtwoord-reset aanvraagt het domein in de mail kunnen bepalen.'),
                'where' => self::SERVER,
                'location' => __('DASHED_TRUSTED_HOSTS voor extra domeinen, bijvoorbeeld staging'),
            ],
            [
                'title' => __('Vertrouwde proxy\'s'),
                'body' => __('Achter Cloudflare of een load balancer ziet de server anders het adres van de proxy in plaats van dat van de bezoeker. Dan werken de IP-lijst, de verzoeklimieten, de meldingen en MFA-bij-IP-wissel niet zoals bedoeld. Controleer op de Beveiligingscheck of het huidige adres jouw echte adres is.'),
                'where' => self::SERVER,
                'location' => __('DASHED_TRUSTED_PROXIES, bijvoorbeeld * of een lijst adressen'),
            ],
            [
                'title' => __('Uploads'),
                'body' => __('Uitvoerbare bestanden (PHP, scripts, .htaccess) worden bij elke upload in het CMS geweigerd, ook als de extensie verstopt zit in de bestandsnaam.'),
                'where' => self::AUTOMATIC,
                'location' => __('Blokkadelijst in dashed-files'),
            ],
            [
                'title' => __('robots.txt'),
                'body' => __('Het CMS, Horizon, de facturen en de bestel- en winkelwagenroutes zijn uitgesloten van zoekmachines. Een statisch robots.txt op de server gaat hiervoor; haal dat weg als het er staat.'),
                'where' => self::AUTOMATIC,
                'location' => __('Route /robots.txt'),
            ],
            [
                'title' => __('Beveiligingscheck'),
                'body' => __('Eén overzicht van wat er aan of uit staat op deze installatie, inclusief de serverinstellingen die vaak misgaan bij een uitrol en de beheerders die MFA nog niet hebben ingesteld.'),
                'where' => self::CMS,
                'location' => __('Instellingen, Beveiligingscheck'),
            ],
        ];
    }
}
