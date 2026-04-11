<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Builder\Block;

abstract class EmailBlock
{
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
