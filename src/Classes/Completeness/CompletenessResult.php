<?php

namespace Dashed\DashedCore\Classes\Completeness;

class CompletenessResult
{
    /**
     * @param  array<string, string>  $missing  sleutel => label van alles wat ontbreekt
     * @param  array<string, string>  $blocking  het deel daarvan dat publiceren tegenhoudt
     */
    public function __construct(
        public int $percentage,
        public array $missing,
        public array $blocking,
    ) {
    }

    public function isComplete(): bool
    {
        return $this->missing === [];
    }

    public function blocksPublishing(): bool
    {
        return $this->blocking !== [];
    }

    public function color(): string
    {
        return match (true) {
            $this->percentage >= 80 => 'success',
            $this->percentage >= 50 => 'warning',
            default => 'danger',
        };
    }
}
