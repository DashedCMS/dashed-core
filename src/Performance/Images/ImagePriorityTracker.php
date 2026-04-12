<?php

namespace Dashed\DashedCore\Performance\Images;

class ImagePriorityTracker
{
    protected int $count = 0;

    public function __construct(
        public readonly int $firstEagerCount = 3,
    ) {
    }

    public function next(): string
    {
        $strategy = $this->count < $this->firstEagerCount ? 'eager' : 'lazy';
        $this->count++;

        return $strategy;
    }

    public function reset(): void
    {
        $this->count = 0;
    }
}
