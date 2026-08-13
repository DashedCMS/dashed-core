<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Builder\Block;

abstract class EmailBlock
{
    public const CONTEXT_TRANSACTIONAL = 'transactional';
    public const CONTEXT_NEWSLETTER = 'newsletter';

    /**
     * Waar dit blok thuishoort. Standaard alleen transactioneel, zodat een
     * blok dat hier niets over zegt nooit ongemerkt in een nieuwsbrief
     * verschijnt. Een bestelsamenvatting in een nieuwsbrief is onzin, en een
     * productraster in een wachtwoord-vergeten-mail net zo goed.
     *
     * @return array<int, string>
     */
    public static function contexts(): array
    {
        return [self::CONTEXT_TRANSACTIONAL];
    }

    public static function inContext(string $context): bool
    {
        return in_array($context, static::contexts(), true);
    }

    abstract public static function key(): string;

    abstract public static function label(): string;

    abstract public static function filamentBlock(): Block;

    abstract public static function render(array $blockData, array $context): string;

    protected static function substitute(string $text, array $context): string
    {
        return preg_replace_callback('/:(\w+):/', function ($m) use ($context) {
            return array_key_exists($m[1], $context) ? (string) $context[$m[1]] : $m[0];
        }, $text);
    }
}
