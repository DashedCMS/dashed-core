<?php

namespace Dashed\DashedCore\Classes\Mail;

use RuntimeException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

/**
 * De blokkadelijst van Postmark: adressen waar Postmark niet meer naartoe
 * stuurt na een hard bounce, een spamklacht of een handmatige blokkade.
 *
 * Werkt met het server-token van de mailer, geen account-token nodig.
 * Suppressions horen bij een message stream, dus elke aanroep gaat per stream;
 * inbound-streams hebben er geen en vallen weg.
 */
class PostmarkSuppressions
{
    public const BASE_URL = 'https://api.postmarkapp.com';

    public const REASONS = ['HardBounce', 'SpamComplaint', 'ManualSuppression'];

    /** Postmark accepteert hooguit vijftig adressen per verwijderverzoek. */
    public const DELETE_CHUNK = 50;

    public static function token(): ?string
    {
        $token = config('mail.mailers.postmark.token')
            ?? config('services.postmark.token')
            ?? config('services.postmark.key');

        return filled($token) ? (string) $token : null;
    }

    public static function configured(): bool
    {
        return static::token() !== null;
    }

    /**
     * @return array<string, string> stream-id => naam
     */
    public static function streams(): array
    {
        $streams = [];

        foreach (static::request()->get('/message-streams', [
            'MessageStreamType' => 'All',
            'IncludeArchivedStreams' => 'false',
        ])->throw()->json('MessageStreams') ?? [] as $stream) {
            if (($stream['MessageStreamType'] ?? null) === 'Inbound') {
                continue;
            }

            $streams[$stream['ID']] = $stream['Name'] ?? $stream['ID'];
        }

        return $streams;
    }

    /**
     * @return array<int, array{email: string, reason: string, origin: string, created_at: string|null, stream: string}>
     */
    public static function list(string $stream, ?string $reason = null, ?string $email = null): array
    {
        $query = array_filter([
            'SuppressionReason' => in_array($reason, static::REASONS, true) ? $reason : null,
            'EmailAddress' => filled($email) ? trim($email) : null,
        ]);

        $rows = [];

        foreach (static::request()->get('/message-streams/' . rawurlencode($stream) . '/suppressions/dump', $query)
            ->throw()->json('Suppressions') ?? [] as $row) {
            $rows[] = [
                'email' => (string) ($row['EmailAddress'] ?? ''),
                'reason' => (string) ($row['SuppressionReason'] ?? ''),
                'origin' => (string) ($row['Origin'] ?? ''),
                'created_at' => $row['CreatedAt'] ?? null,
                'stream' => $stream,
            ];
        }

        return $rows;
    }

    /**
     * Haalt adressen van de blokkadelijst van één stream. Een spamklacht kan
     * Postmark niet opheffen; die komt terug als mislukt.
     *
     * @param  array<int, string>  $emails
     * @return array{deleted: array<int, string>, failed: array<string, string>}
     */
    public static function reactivate(string $stream, array $emails): array
    {
        $deleted = [];
        $failed = [];

        foreach (array_chunk(array_values(array_unique($emails)), static::DELETE_CHUNK) as $chunk) {
            $results = static::request()
                ->post('/message-streams/' . rawurlencode($stream) . '/suppressions/delete', [
                    'Suppressions' => array_map(fn (string $email) => ['EmailAddress' => $email], $chunk),
                ])
                ->throw()
                ->json('Suppressions') ?? [];

            foreach ($results as $result) {
                $email = (string) ($result['EmailAddress'] ?? '');

                if (($result['Status'] ?? null) === 'Deleted') {
                    $deleted[] = $email;
                } else {
                    $failed[$email] = (string) ($result['Message'] ?? __('Onbekende fout'));
                }
            }
        }

        return ['deleted' => $deleted, 'failed' => $failed];
    }

    public static function reasonLabel(string $reason): string
    {
        return match ($reason) {
            'HardBounce' => __('Hard bounce'),
            'SpamComplaint' => __('Spamklacht'),
            'ManualSuppression' => __('Handmatig geblokkeerd'),
            default => $reason,
        };
    }

    public static function originLabel(string $origin): string
    {
        return match ($origin) {
            'Recipient' => __('Ontvanger'),
            'Customer' => __('Via de API of het dashboard'),
            'Admin' => __('Postmark'),
            default => $origin,
        };
    }

    protected static function request(): PendingRequest
    {
        $token = static::token();

        if ($token === null) {
            throw new RuntimeException('Geen Postmark-token ingesteld.');
        }

        return Http::baseUrl(static::BASE_URL)
            ->acceptJson()
            ->withHeaders(['X-Postmark-Server-Token' => $token])
            ->timeout(15);
    }
}
