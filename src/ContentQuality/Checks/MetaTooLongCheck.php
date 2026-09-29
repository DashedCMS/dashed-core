<?php

// packages/dashed/dashed-core/src/ContentQuality/Checks/MetaTooLongCheck.php

namespace Dashed\DashedCore\ContentQuality\Checks;

class MetaTooLongCheck extends MetaLengthCheck
{
    public function key(): string
    {
        return 'meta_too_long';
    }

    public function label(): string
    {
        return 'Meta-tekst te lang';
    }

    protected function fields(): array
    {
        return ['title', 'description'];
    }

    protected function fails(string $value, int $limit): bool
    {
        return mb_strlen($value) > $limit;
    }

    protected function describe(string $field, string $locale, string $value, int $limit): string
    {
        return ucfirst($field) . ' ' . strtoupper($locale) . ' (' . mb_strlen($value) . '/' . $limit . ')';
    }
}
