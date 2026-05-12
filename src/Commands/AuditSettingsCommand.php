<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Settings\SettingsRegistry;

class AuditSettingsCommand extends Command
{
    protected $signature = 'dashed:settings:audit
        {--strict : Exit 1 if any auto-registered keys remain}
        {--save : Also write a markdown report to storage/logs}';

    protected $description = 'Audit Customsetting keys: explicit vs auto-registered.';

    public function handle(SettingsRegistry $registry): int
    {
        $all = $registry->all();
        $explicit = array_filter($all, fn ($s) => $s->explicit);
        $auto = array_filter($all, fn ($s) => ! $s->explicit);

        uasort($explicit, fn ($a, $b) => [$a->package, $a->key] <=> [$b->package, $b->key]);
        uasort($auto, fn ($a, $b) => $a->firstSeenAt <=> $b->firstSeenAt);

        $this->info('Explicitly registered (' . count($explicit) . ')');
        $this->table(
            ['Package', 'Key', 'Type', 'Default', 'Label'],
            array_map(fn ($s) => [
                $s->package,
                $s->key,
                $s->type,
                $this->stringify($s->default),
                $s->label ?? '',
            ], array_values($explicit)),
        );

        $this->warn('Auto-registered (review) (' . count($auto) . ')');
        $this->table(
            ['Key', 'Default', 'Caller'],
            array_map(fn ($s) => [
                $s->key,
                $this->stringify($s->default),
                $s->caller ?? '(unknown)',
            ], array_values($auto)),
        );

        if ($this->option('save')) {
            $this->writeReport($explicit, $auto);
        }

        if ($this->option('strict') && count($auto) > 0) {
            $this->error('Strict mode: ' . count($auto) . ' auto-registered keys remain.');
            return 1;
        }

        return 0;
    }

    protected function stringify(mixed $value): string
    {
        if ($value === null) return 'null';
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_scalar($value)) return (string) $value;
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function writeReport(array $explicit, array $auto): void
    {
        $dir = config('dashed-settings.audit.report_dir', storage_path('logs'));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = rtrim($dir, '/') . '/settings-audit-' . date('Y-m-d-His') . '.md';
        $lines = ['# Settings audit', '', '_Generated ' . date(DATE_ATOM) . '_', ''];

        $lines[] = '## Explicitly registered';
        $lines[] = '';
        $lines[] = '| Package | Key | Type | Default | Label |';
        $lines[] = '| --- | --- | --- | --- | --- |';
        foreach ($explicit as $s) {
            $lines[] = sprintf('| %s | %s | %s | %s | %s |',
                $s->package, $s->key, $s->type, $this->stringify($s->default), $s->label ?? '');
        }
        $lines[] = '';

        $lines[] = '## Auto-registered (review)';
        $lines[] = '';
        $lines[] = '| Key | Default | Caller |';
        $lines[] = '| --- | --- | --- |';
        foreach ($auto as $s) {
            $lines[] = sprintf('| %s | %s | %s |',
                $s->key, $this->stringify($s->default), $s->caller ?? '(unknown)');
        }

        file_put_contents($path, implode("\n", $lines) . "\n");
        $this->info("Report written to {$path}");
    }
}
