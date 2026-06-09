<?php

namespace Dashed\DashedCore\Services\Summary;

use Dashed\DashedCore\Services\Summary\DTOs\SummarySection;

class SummarySectionSerializer
{
    /** @param array<int, SummarySection> $sections */
    public function toText(array $sections): string
    {
        $parts = [];

        foreach ($sections as $section) {
            if (! $section instanceof SummarySection) {
                continue;
            }

            $lines = ['## ' . $section->title];

            foreach ($section->blocks as $block) {
                $line = $this->block($block);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }

            $parts[] = implode("\n", $lines);
        }

        return implode("\n\n", $parts);
    }

    /** @param array<string, mixed> $block */
    private function block(array $block): string
    {
        $type = $block['type'] ?? null;
        $data = $block['data'] ?? [];

        return match ($type) {
            'heading' => '### ' . (string) ($data['content'] ?? ''),
            'text' => (string) ($data['content'] ?? ''),
            'stats' => $this->stats($data),
            'table' => $this->table($data),
            default => '',
        };
    }

    private function stats(array $data): string
    {
        $rows = $data['rows'] ?? [];
        $lines = [];
        foreach ($rows as $row) {
            $label = (string) ($row['label'] ?? '');
            $value = (string) ($row['value'] ?? '');
            if ($label !== '' || $value !== '') {
                $lines[] = '- ' . $label . ': ' . $value;
            }
        }

        return implode("\n", $lines);
    }

    private function table(array $data): string
    {
        $lines = [];
        $headers = $data['headers'] ?? [];
        if (is_array($headers) && $headers !== []) {
            $lines[] = implode(' | ', array_map('strval', $headers));
        }
        foreach ($data['rows'] ?? [] as $row) {
            if (is_array($row)) {
                $lines[] = implode(' | ', array_map('strval', $row));
            }
        }

        return implode("\n", $lines);
    }
}
