<?php

namespace Dashed\DashedCore\Webhooks\Outgoing;

use Closure;

/**
 * Een webhook-URL komt van buiten. Zonder deze controle kan iemand het CMS
 * berichten laten sturen naar diensten in het eigen netwerk (metadata van de
 * cloudprovider, Redis, een beheerpaneel). Daarom alleen https, geen
 * inloggegevens in de URL, en geen enkel adres waar de host naar resolvet
 * mag intern zijn. pinned() zet de host daarna vast op het gecontroleerde
 * adres, zodat een tweede DNS-antwoord tijdens het versturen er niet
 * alsnog een intern adres van maakt.
 */
final class UrlGuard
{
    private static ?Closure $resolver = null;

    public static function resolveUsing(?Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    public static function check(string $url): ?string
    {
        return self::inspect($url)['reason'];
    }

    /**
     * @return array{0: ?string, 1: array<int, mixed>}
     */
    public static function pinned(string $url): array
    {
        $result = self::inspect($url);

        if ($result['reason'] !== null) {
            return [$result['reason'], []];
        }

        if ($result['literal']) {
            return [null, []];
        }

        // curl verwacht een IPv6-adres tussen blokhaken; zonder negeert hij de
        // regel en resolvet hij zelf opnieuw, en dan is het vastzetten weg.
        $ip = $result['ips'][0];
        $address = str_contains($ip, ':') ? '[' . $ip . ']' : $ip;

        return [null, [CURLOPT_RESOLVE => [$result['host'] . ':' . $result['port'] . ':' . $address]]];
    }

    /**
     * @return array{reason: ?string, host: string, port: int, ips: list<string>, literal: bool}
     */
    private static function inspect(string $url): array
    {
        $fail = fn (string $reason) => ['reason' => $reason, 'host' => '', 'port' => 443, 'ips' => [], 'literal' => false];
        $parts = $url === '' ? false : parse_url($url);

        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return $fail(__('Alleen een volledige https-URL is toegestaan.'));
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return $fail(__('Inloggegevens in de URL zijn niet toegestaan.'));
        }

        $host = trim($parts['host'], '[]');
        $port = (int) ($parts['port'] ?? 443);
        $literal = filter_var($host, FILTER_VALIDATE_IP) !== false;
        $ips = $literal ? [$host] : self::resolve($host);

        if ($ips === []) {
            return $fail(__('De host :host is niet te vinden.', ['host' => $host]));
        }

        foreach ($ips as $ip) {
            if (! self::isPublic($ip)) {
                return $fail(__('De host :host wijst naar een intern adres.', ['host' => $host]));
            }
        }

        return ['reason' => null, 'host' => $host, 'port' => $port, 'ips' => array_values($ips), 'literal' => $literal];
    }

    private static function isPublic(string $ip): bool
    {
        // ::ffff:127.0.0.1 is gewoon 127.0.0.1; los toetsen, want niet elke
        // PHP-versie rekent die vorm tot het gereserveerde bereik.
        if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $m)) {
            $ip = $m[1];
        }

        // GLOBAL_RANGE sluit ook CGNAT (100.64/10) en benchmarkreeksen
        // (198.18/15) uit, die NO_PRIV en NO_RES doorlaten.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE) === false) {
            return false;
        }

        // 6to4 (2002::/16) en NAT64 (64:ff9b::/96) verpakken een IPv4-adres,
        // dat intern kan zijn. Die tunnels zijn voor een webhook niet nodig.
        $packed = inet_pton($ip);

        if ($packed !== false && strlen($packed) === 16) {
            if (str_starts_with($packed, "\x20\x02")) {
                return false;
            }

            if (str_starts_with($packed, "\x00\x64\xff\x9b" . str_repeat("\x00", 8))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private static function resolve(string $host): array
    {
        if (self::$resolver !== null) {
            return array_values((self::$resolver)($host));
        }

        $ips = gethostbynamel($host) ?: [];

        foreach ((@dns_get_record($host, DNS_AAAA) ?: []) as $record) {
            if (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }
}
