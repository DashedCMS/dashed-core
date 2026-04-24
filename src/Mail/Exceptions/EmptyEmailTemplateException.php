<?php

namespace Dashed\DashedCore\Mail\Exceptions;

use RuntimeException;

class EmptyEmailTemplateException extends RuntimeException
{
    public function __construct(string $message, private array $context = [])
    {
        parent::__construct($message);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
