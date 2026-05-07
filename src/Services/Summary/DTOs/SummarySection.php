<?php

namespace Dashed\DashedCore\Services\Summary\DTOs;

/**
 * Immutable sectie-DTO. Bevat de sectie-titel en een lijst van
 * block-arrays die door de unified email-renderer omgezet worden
 * naar HTML-rijen in de samenvatting-mail. Toegestane block-types
 * zijn de standaard email-blocks (heading, text, divider, button)
 * plus de speciaal voor samenvatting-mails toegevoegde 'stats' en
 * 'table' blocks.
 *
 * @phpstan-type Block array{type: string, data?: array<string, mixed>}
 */
class SummarySection
{
    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public function __construct(
        public readonly string $title,
        public readonly array $blocks,
    ) {
    }
}
