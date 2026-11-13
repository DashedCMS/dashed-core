<?php

namespace Dashed\DashedCore\Http;

use Throwable;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use Dashed\DashedCore\Webhooks\Outgoing\UrlGuard;

/**
 * Haalt een bestand van een URL die van buiten komt (een beheerder plakt hem,
 * of een import leest hem uit een feed). Zonder deze klasse is "download deze
 * afbeelding" een manier om het CMS een dienst in het eigen netwerk te laten
 * aanspreken of de schijf vol te laten lopen. Daarom gaat elke sprong, ook
 * elke redirect, door UrlGuard en wordt hij vastgezet op het gecontroleerde
 * adres, is er een harde grens op de grootte, en telt alleen het gesnoven
 * inhoudstype: de Content-Type-header zegt wat de afzender wil dat we geloven.
 *
 * De aanroeper is eigenaar van het teruggegeven bestand en ruimt het zelf op.
 */
final class SafeDownloader
{
    public const DEFAULT_MAX_BYTES = 25 * 1024 * 1024;

    // De code van een DownloadRejectedException: waarom er geweigerd is, voor
    // een aanroeper die een oordeel over de inhoud (te groot, verkeerd type)
    // wil onderscheiden van "niet bereikbaar of niet toegestaan". 0 is al het andere.
    public const REASON_TOO_LARGE = 1;

    public const REASON_TYPE = 2;

    private const REDIRECT_STATUSES = [301, 302, 303, 307, 308];

    /**
     * `deadline` is de totale tijd in seconden, over DNS, alle
     * doorverwijzingen en de body samen (standaard gelijk aan `timeout`,
     * en die standaard 20). Elke sprong krijgt de resterende tijd als
     * time-out; daarnaast is `timeout` de grens per sprong. Zo kan een
     * reeks trage doorverwijzingen de wachttijd niet vermenigvuldigen.
     *
     * Een opgegeven `allowed_hosts` die na opschonen leeg is, weigert alles.
     *
     * @param  array{max_bytes?: int, timeout?: int|float, deadline?: int|float, connect_timeout?: int|float, max_redirects?: int, allowed_mimes?: list<string>, headers?: array<string, string>, allow_http?: bool, allowed_ports?: list<int>, trusted_hosts?: list<string>, allowed_hosts?: list<string>}  $options
     *
     * @throws DownloadRejectedException
     */
    public static function fetch(string $url, array $options = []): DownloadedFile
    {
        $maxBytes = max(1, (int) ($options['max_bytes'] ?? self::DEFAULT_MAX_BYTES));
        $maxRedirects = max(0, (int) ($options['max_redirects'] ?? 3));
        $allowedMimes = array_map('strtolower', array_values($options['allowed_mimes'] ?? []));
        $headers = (array) ($options['headers'] ?? []);
        $firstHost = strtolower((string) parse_url($url, PHP_URL_HOST));
        $perHop = is_numeric($options['timeout'] ?? null) && $options['timeout'] > 0 ? (float) $options['timeout'] : 20.0;
        $total = is_numeric($options['deadline'] ?? null) && $options['deadline'] > 0 ? (float) $options['deadline'] : $perHop;
        $options['_deadline'] = microtime(true) + $total;
        $options['timeout'] = is_numeric($options['timeout'] ?? null) && $options['timeout'] > 0 ? $options['timeout'] : $perHop;

        $path = null;

        try {
            $current = $url;

            for ($hop = 0; ; $hop++) {
                $curl = self::guard($current, $options);
                // De DNS-toets in guard() kan zelf tijd kosten.
                self::remaining($options);
                $path ??= self::temporaryPath();

                // Eigen headers (een Authorization bijvoorbeeld) horen bij de
                // host waarvoor ze zijn meegegeven, niet bij waar die naar doorstuurt.
                $sameHost = strtolower((string) parse_url($current, PHP_URL_HOST)) === $firstHost;

                $response = self::send($current, $path, $curl, $sameHost ? $headers : [], $maxBytes, $options);

                if (! in_array($response->status(), self::REDIRECT_STATUSES, true)) {
                    break;
                }

                if ($hop >= $maxRedirects) {
                    throw new DownloadRejectedException(__('De URL verwijst te vaak door.'));
                }

                $current = self::redirectTarget($current, $response);
            }

            if (! $response->successful()) {
                throw new DownloadRejectedException(__('De server antwoordde met status :status.', ['status' => $response->status()]));
            }

            clearstatcache(true, $path);
            $size = (int) @filesize($path);

            if ($size === 0) {
                throw new DownloadRejectedException(__('Het antwoord is leeg.'));
            }

            // Vangnet naast het schrijffilter, voor een handler die de sink anders vult.
            if ($size > $maxBytes) {
                throw new DownloadRejectedException(self::tooLargeMessage($maxBytes), self::REASON_TOO_LARGE);
            }

            $mime = strtolower((string) (new \finfo(FILEINFO_MIME_TYPE))->file($path));

            if ($mime === '') {
                throw new DownloadRejectedException(__('Het bestandstype is niet vast te stellen.'));
            }

            if ($allowedMimes !== [] && ! in_array($mime, $allowedMimes, true)) {
                throw new DownloadRejectedException(__('Het bestandstype :mime is niet toegestaan.', ['mime' => $mime]), self::REASON_TYPE);
            }

            return new DownloadedFile($path, $mime, $size, $current, self::suggestedName($current));
        } catch (Throwable $e) {
            if ($path !== null && is_file($path)) {
                @unlink($path);
            }

            if ($e instanceof DownloadRejectedException) {
                throw $e;
            }

            throw new DownloadRejectedException(__('Het bestand kon niet worden opgehaald: de server reageerde niet of te traag.'), 0, $e);
        }
    }

    /**
     * @return array<int, mixed> De curl-opties die de host vastzetten.
     */
    private static function guard(string $url, array $options): array
    {
        $allowHttp = (bool) ($options['allow_http'] ?? false);
        $parts = parse_url($url);
        $scheme = strtolower((string) (is_array($parts) ? ($parts['scheme'] ?? '') : ''));
        $host = strtolower(trim((string) (is_array($parts) ? ($parts['host'] ?? '') : ''), '[]'));

        if (! in_array($scheme, $allowHttp ? ['http', 'https'] : ['https'], true) || $host === '') {
            throw new DownloadRejectedException($allowHttp
                ? __('Alleen een volledige http- of https-URL is toegestaan.')
                : __('Alleen een volledige https-URL is toegestaan.'));
        }

        // UrlGuard laat elke poort toe (een webhook mag op 8443 luisteren);
        // een download niet, anders is dit een poortscanner voor het internet.
        $port = (int) ($parts['port'] ?? ($scheme === 'http' ? 80 : 443));
        $allowedPorts = array_map('intval', (array) ($options['allowed_ports'] ?? ($allowHttp ? [80, 443] : [443])));

        if (! in_array($port, $allowedPorts, true)) {
            throw new DownloadRejectedException($allowedPorts === [443]
                ? __('Alleen poort 443 is toegestaan.')
                : __('Alleen poort :ports is toegestaan.', ['ports' => implode(', ', $allowedPorts)]));
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new DownloadRejectedException(__('Inloggegevens in de URL zijn niet toegestaan.'));
        }

        // Een vaste lijst hosts (de bestandsservers van een aanbieder): elke
        // sprong, ook een doorverwijzing, moet erop staan. Dit maakt de toets
        // alleen strenger; het adres zelf wordt hieronder nog gewoon gecontroleerd.
        // Opgegeven maar leeg (of alleen onzin) is geen "geen beperking": dan mag niets.
        $restricted = array_key_exists('allowed_hosts', $options) && $options['allowed_hosts'] !== null;
        $allowedHosts = array_values(array_filter(array_map(fn ($name) => is_string($name) ? strtolower(trim($name)) : '', is_array($options['allowed_hosts'] ?? null) ? $options['allowed_hosts'] : [])));

        if ($restricted && ! in_array($host, $allowedHosts, true)) {
            throw new DownloadRejectedException(__('Dit adres hoort niet bij de toegestane bestandsservers.'));
        }

        // Een vertrouwde host slaat de toets op interne adressen over, en
        // daarmee ook het vastzetten van het adres: wie hier een host noemt
        // geeft dit proces toegang tot wat die naam op dat moment aanwijst.
        $trusted = array_map(fn ($name) => strtolower(trim((string) $name)), (array) ($options['trusted_hosts'] ?? []));

        if (filter_var($host, FILTER_VALIDATE_IP) === false && in_array($host, $trusted, true)) {
            return [];
        }

        // UrlGuard kent alleen https. Het krijgt daarom dezelfde host met de
        // echte poort onder https aangeboden: de toets op het adres en de
        // regel die de host vastzet zijn voor http dezelfde.
        [$reason, $curl] = UrlGuard::pinned('https://' . $parts['host'] . ':' . $port . '/');

        if ($reason !== null) {
            throw new DownloadRejectedException($reason);
        }

        return $curl;
    }

    /**
     * Eén sprong, zonder redirects te volgen. Het tijdelijke bestand wordt
     * per sprong opnieuw geopend, zodat de body van een redirect-antwoord
     * niet vóór het echte bestand blijft staan.
     */
    private static function send(string $url, string $path, array $curl, array $headers, int $maxBytes, array $options): Response
    {
        ByteLimitFilter::register();

        $state = (object) ['max' => $maxBytes, 'received' => 0, 'exceeded' => false, 'declaredTooLarge' => false];
        $handle = fopen($path, 'wb');

        if (! $handle) {
            throw new DownloadRejectedException(__('Het tijdelijke bestand kon niet worden aangemaakt.'));
        }

        stream_filter_append($handle, ByteLimitFilter::NAME, STREAM_FILTER_WRITE, $state);

        $remaining = self::remaining($options);
        $deadline = (float) ($options['_deadline'] ?? (microtime(true) + $remaining));

        // De resterende tijd, maar de opgegeven waarde zolang die nog past:
        // de deadline zelf bewaakt daarnaast de progress-haak hieronder.
        $timeout = $options['timeout'] ?? 20;
        $connect = $options['connect_timeout'] ?? 5;

        try {
            $response = Http::timeout((float) $timeout <= $remaining + 1 ? $timeout : $remaining)
                ->connectTimeout((float) $connect <= $remaining ? $connect : $remaining)
                ->withHeaders($headers)
                ->withOptions([
                    'allow_redirects' => false,
                    'curl' => $curl,
                    'sink' => $handle,
                    // Vangnet naast de time-out: een body die druppelt breekt af op de deadline.
                    'progress' => function () use ($deadline): void {
                        if (microtime(true) > $deadline) {
                            throw new DownloadRejectedException(__('Het bestand kon niet worden opgehaald: de server reageerde niet of te traag.'));
                        }
                    },
                    // Weigert vóór de body binnenkomt; bij een nagebootst
                    // antwoord (Http::fake) loopt deze haak niet en vangt de
                    // controle hieronder hetzelfde af.
                    'on_headers' => function (ResponseInterface $response) use ($state, $maxBytes): void {
                        if (self::declaredLength($response->getHeaderLine('Content-Length')) > $maxBytes) {
                            $state->declaredTooLarge = true;

                            throw new DownloadRejectedException(self::tooLargeMessage($maxBytes), self::REASON_TOO_LARGE);
                        }
                    },
                ])
                ->get($url);
        } catch (Throwable $e) {
            if ($state->exceeded || $state->declaredTooLarge) {
                throw new DownloadRejectedException(self::tooLargeMessage($maxBytes), self::REASON_TOO_LARGE, $e);
            }

            throw $e;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        if ($state->exceeded || self::declaredLength($response->header('Content-Length')) > $maxBytes) {
            throw new DownloadRejectedException(self::tooLargeMessage($maxBytes), self::REASON_TOO_LARGE);
        }

        return $response;
    }

    /**
     * De tijd die er nog is tot de deadline, in seconden.
     *
     * @throws DownloadRejectedException  als de deadline al voorbij is
     */
    private static function remaining(array $options): float
    {
        $left = (float) ($options['_deadline'] ?? (microtime(true) + 20)) - microtime(true);

        if ($left <= 0.05) {
            throw new DownloadRejectedException(__('Het bestand kon niet worden opgehaald: de server reageerde niet of te traag.'));
        }

        return $left;
    }

    private static function declaredLength(?string $header): int
    {
        $header = trim((string) $header);

        return ctype_digit($header) ? (int) $header : 0;
    }

    private static function redirectTarget(string $from, Response $response): string
    {
        $location = trim((string) $response->header('Location'));

        if ($location === '') {
            throw new DownloadRejectedException(__('De URL verwijst door zonder doel.'));
        }

        try {
            return (string) UriResolver::resolve(new Uri($from), new Uri($location));
        } catch (Throwable) {
            throw new DownloadRejectedException(__('De URL verwijst door naar een ongeldig adres.'));
        }
    }

    private static function temporaryPath(): string
    {
        $directory = storage_path('app/media-ingest');

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new DownloadRejectedException(__('Het tijdelijke bestand kon niet worden aangemaakt.'));
        }

        return $directory . DIRECTORY_SEPARATOR . Str::random(40);
    }

    private static function suggestedName(string $url): ?string
    {
        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        $name = basename(str_replace('\\', '/', $path));
        $name = trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\]+/', '', $name), " .\t");

        return $name === '' ? null : $name;
    }

    private static function tooLargeMessage(int $maxBytes): string
    {
        $max = $maxBytes >= 1024 * 1024
            ? round($maxBytes / 1024 / 1024, 1) . ' MB'
            : max(1, (int) ceil($maxBytes / 1024)) . ' kB';

        return __('Het bestand is groter dan de toegestane :max.', ['max' => $max]);
    }
}
