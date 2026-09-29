<?php

// packages/dashed/dashed-core/src/ContentQuality/Checks/MetaTruncatedCheck.php

namespace Dashed\DashedCore\ContentQuality\Checks;

/**
 * Descriptions that were hard-cut at the length limit by the old
 * Metadata::saved hook: exactly (or, after rtrim, one below) the limit
 * and not ending like a sentence. Titles are left out: they rarely end
 * with punctuation, so the same heuristic would flag every long title.
 */
class MetaTruncatedCheck extends MetaLengthCheck
{
    protected const SENTENCE_END = '/[.!?…)\]"\'»”]$/u';

    public function key(): string
    {
        return 'meta_truncated';
    }

    public function label(): string
    {
        return 'Meta-omschrijving afgebroken';
    }

    protected function fields(): array
    {
        return ['description'];
    }

    protected function fails(string $value, int $limit): bool
    {
        $length = mb_strlen($value);

        return $length >= $limit - 1
            && $length <= $limit
            && ! preg_match(self::SENTENCE_END, rtrim($value));
    }

    protected function describe(string $field, string $locale, string $value, int $limit): string
    {
        return strtoupper($locale) . ': "…' . mb_substr(rtrim($value), -25) . '"';
    }
}
