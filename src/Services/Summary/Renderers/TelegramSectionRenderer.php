<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Services\Summary\Renderers;

use Dashed\DashedCore\Notifications\DTOs\TelegramSummary;
use Dashed\DashedCore\Services\Summary\DTOs\SummaryPeriod;
use Dashed\DashedCore\Services\Summary\DTOs\SummarySection;

/**
 * Vertaalt een SummarySection (heading/text/stats/table-blocks) naar
 * een TelegramSummary-DTO. Telegram-berichten zijn beknopt, dus we
 * flatten tabel-rijen tot label/value-paren en slaan visuele blocks
 * als dividers/buttons over.
 */
final class TelegramSectionRenderer
{
    public static function render(SummarySection $section, SummaryPeriod $period): TelegramSummary
    {
        $fields = [];

        foreach ($section->blocks as $block) {
            $type = (string) ($block['type'] ?? '');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            match ($type) {
                'stats' => self::appendStats($fields, $data),
                'table' => self::appendTable($fields, $data),
                'heading' => self::appendHeading($fields, $data),
                'text', 'paragraph' => self::appendText($fields, $data),
                default => null,
            };
        }

        return new TelegramSummary(
            title: $section->title . ' — ' . $period->label,
            fields: $fields,
            emoji: '📊',
        );
    }

    /**
     * @param  array<string, string|int|float|null>  $fields
     * @param  array<string, mixed>  $data
     */
    private static function appendStats(array &$fields, array $data): void
    {
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $value = $row['value'] ?? null;
            if ($label === '' || $value === null || $value === '') {
                continue;
            }
            $fields[$label] = is_scalar($value) ? (string) $value : null;
        }
    }

    /**
     * @param  array<string, string|int|float|null>  $fields
     * @param  array<string, mixed>  $data
     */
    private static function appendTable(array &$fields, array $data): void
    {
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        foreach ($rows as $row) {
            if (! is_array($row) || count($row) === 0) {
                continue;
            }

            $values = array_values($row);
            $label = trim((string) array_shift($values));
            if ($label === '') {
                continue;
            }

            $rest = array_filter(array_map(
                fn ($v) => is_scalar($v) ? trim((string) $v) : '',
                $values,
            ), fn ($v) => $v !== '');

            $fields[$label] = $rest === [] ? null : implode(' • ', $rest);
        }
    }

    /**
     * @param  array<string, string|int|float|null>  $fields
     * @param  array<string, mixed>  $data
     */
    private static function appendHeading(array &$fields, array $data): void
    {
        $content = trim((string) ($data['content'] ?? ''));
        if ($content === '') {
            return;
        }
        // Headings worden in Telegram getoond als veld zonder waarde,
        // zodat ze visueel tussen blokken staan zonder dat de TelegramSummary
        // markdown-renderer breekt.
        $fields[$content] = '—';
    }

    /**
     * @param  array<string, string|int|float|null>  $fields
     * @param  array<string, mixed>  $data
     */
    private static function appendText(array &$fields, array $data): void
    {
        $raw = trim((string) ($data['content'] ?? ''));
        if ($raw === '') {
            return;
        }

        // HTML strippen: de email-renderer accepteert paragraph-HTML,
        // maar Telegram heeft alleen plain text nodig.
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($raw)) ?? '');
        if ($plain === '') {
            return;
        }

        $fields['Info'] = $plain;
    }
}
