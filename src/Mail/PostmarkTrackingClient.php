<?php

namespace Dashed\DashedCore\Mail;

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Maakt de headers X-PM-Track-Links en X-PM-Track-Opens echte API-velden.
 *
 * Symfony's Postmark-transport stuurt ze als gewone mailheaders mee, en die
 * negeert Postmark: dan geldt altijd de serverinstelling (getest 30 okt 2026).
 */
final class PostmarkTrackingClient implements HttpClientInterface
{
    use DecoratorTrait;

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (isset($options['json']['Headers'])) {
            $options['json'] = self::withTrackingFields($options['json']);
        }

        return $this->client->request($method, $url, $options);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function withTrackingFields(array $payload): array
    {
        $headers = [];

        foreach ($payload['Headers'] as $header) {
            match (strtolower($header['Name'])) {
                'x-pm-track-links' => $payload['TrackLinks'] = $header['Value'],
                'x-pm-track-opens' => $payload['TrackOpens'] = filter_var($header['Value'], FILTER_VALIDATE_BOOLEAN),
                default => $headers[] = $header,
            };
        }

        $payload['Headers'] = $headers;

        return $payload;
    }
}
