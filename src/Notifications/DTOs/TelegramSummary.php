<?php

namespace Dashed\DashedCore\Notifications\DTOs;

final class TelegramSummary
{
    /**
     * @param  array<string, string|int|float|null>  $fields
     */
    public function __construct(
        public readonly string $title,
        public readonly array $fields = [],
        public readonly ?string $adminUrl = null,
        public readonly ?string $emoji = null,
        public readonly ?string $linkLabel = null,
    ) {
    }

    public function toMarkdown(): string
    {
        $lines = [];

        $titleLine = $this->emoji ? $this->emoji . ' ' : '';
        $titleLine .= '*' . $this->escape($this->title) . '*';
        $lines[] = $titleLine;

        foreach ($this->fields as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $lines[] = '*' . $this->escape((string) $label) . ':* ' . $this->escape((string) $value);
        }

        if ($this->adminUrl !== null && $this->adminUrl !== '') {
            $lines[] = '';
            $label = $this->linkLabel ?? 'Bekijk in admin';
            $escapedUrl = $this->escapeLinkUrl($this->adminUrl);
            $lines[] = '[' . $this->escape($label) . '](' . $escapedUrl . ')';
        }

        return implode("\n", $lines);
    }

    private function escape(string $text): string
    {
        return preg_replace('/([\\\\_*\[\]()~`>#+\-=|{}.!])/', '\\\\$1', $text);
    }

    private function escapeLinkUrl(string $url): string
    {
        return str_replace(['\\', ')'], ['\\\\', '\\)'], $url);
    }
}
