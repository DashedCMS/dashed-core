<?php

namespace Dashed\DashedCore\Mail;

class EmailTemplateRegistry
{
    /** @var array<int, class-string> */
    protected array $mailables = [];

    public function register(string $mailableClass): void
    {
        if (! in_array($mailableClass, $this->mailables, true)) {
            $this->mailables[] = $mailableClass;
        }
    }

    /** @return array<int, class-string> */
    public function all(): array
    {
        return $this->mailables;
    }

    public function find(string $key): ?string
    {
        foreach ($this->mailables as $class) {
            if ($class::emailTemplateKey() === $key) {
                return $class;
            }
        }

        return null;
    }
}
