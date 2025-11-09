<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;

class MigrateDatabaseToV4 extends Command
{
    protected $signature = 'dashed:migrate-database-to-v4';
    protected $description = 'Recursively convert strings containing data-youtube-video into Tiptap doc with externalVideo node';

    /**
     * ✅ Pas deze arrays aan naar wens
     */
    protected array $columnNameWhitelist = [
        'content', 'body', 'description', 'excerpt', 'text', 'html',
        'rich_content', 'richContent', 'data',
    ];

    protected array $skipTables = [
        'dashed__custom_settings',
        'dashed__translations',
    ];

    public function handle()
    {
        $db = \DB::getDatabaseName();
        $this->info("Scanning database: {$db}");

        foreach ($this->getAllTables() as $table) {
            if ($this->shouldSkipTable($table)) {
                $this->line("→ Table: {$table}");
                $this->line("  (skip) table is in skip list");

                continue;
            }

            $this->line("→ Table: {$table}");

            $columns = $this->getProcessableColumns($table);
            if (empty($columns)) {
                $this->line("  (skip) no processable columns");

                continue;
            }

            $pk = $this->getPrimaryKeyColumn($table);
            if ($pk) {
                $this->processTableChunkedById($table, $pk, $columns);
            } else {
                $this->warn("  (no PK) falling back to offset-chunks");
                $this->processTableByOffset($table, $columns);
            }
        }

        $this->info('Done ✅');
    }

    /* -----------------------------------------------------------------------------
     | Schema helpers
     * ---------------------------------------------------------------------------*/

    public function getAllTables(): array
    {
        $rows = \DB::select('SHOW TABLES');
        $tables = array_map(fn ($r) => array_values((array) $r)[0], $rows);

        // Filter skip-lijst
        return array_values(array_filter($tables, fn ($t) => ! $this->shouldSkipTable($t)));
    }

    protected function shouldSkipTable(string $table): bool
    {
        return in_array($table, $this->skipTables, true);
    }

    public function getPrimaryKeyColumn(string $table): ?string
    {
        $db = \DB::getDatabaseName();

        $row = \DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('COLUMN_NAME')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', 'PRIMARY')
            ->orderBy('ORDINAL_POSITION')
            ->first();

        return $row?->COLUMN_NAME ?? null;
    }

    /**
     * Alleen kolommen die:
     * - in de whitelist staan, én
     * - text-achtig type hebben
     */
    public function getProcessableColumns(string $table): array
    {
        $db = \DB::getDatabaseName();

        $rows = \DB::table('information_schema.COLUMNS')
            ->select('COLUMN_NAME', 'DATA_TYPE')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', $table)
            ->whereIn('COLUMN_NAME', $this->columnNameWhitelist)
            ->get();

        $processableTypes = ['json', 'text', 'mediumtext', 'longtext', 'varchar', 'char'];

        return collect($rows)
            ->filter(fn ($col) => in_array(strtolower($col->DATA_TYPE), $processableTypes, true))
            ->pluck('COLUMN_NAME')
            ->all();
    }

    /* -----------------------------------------------------------------------------
     | Processing
     * ---------------------------------------------------------------------------*/

    public function processTableChunkedById(string $table, string $pk, array $columns): void
    {
        $count = \DB::table($table)->count();
        $this->line("  Rows: {$count}; PK: {$pk}; Columns: " . implode(', ', $columns));

        \DB::table($table)
            ->select(array_merge([$pk], $columns))
            ->orderBy($pk)
            ->chunkById(500, function ($rows) use ($table, $pk, $columns) {
                foreach ($rows as $row) {
                    $updates = $this->processRowColumns($row, $columns);
                    if (! empty($updates)) {
                        \DB::table($table)->where($pk, $row->$pk)->update($updates);
                    }
                }
            });
    }

    public function processTableByOffset(string $table, array $columns): void
    {
        $count = \DB::table($table)->count();
        $this->line("  Rows: {$count}; Columns: " . implode(', ', $columns));

        $offset = 0;
        $limit = 500;

        while (true) {
            $rows = \DB::table($table)->offset($offset)->limit($limit)->get();
            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                $where = $this->buildUniqueWhereFromRow($row);
                $updates = $this->processRowColumns($row, $columns);
                if (! empty($updates)) {
                    \DB::table($table)->where($where)->update($updates);
                }
            }

            $offset += $limit;
        }
    }

    public function buildUniqueWhereFromRow(object $row): array
    {
        $arr = (array) $row;

        if (array_key_exists('id', $arr)) {
            return ['id' => $arr['id']];
        }

        foreach (['uuid', 'slug', 'code'] as $likely) {
            if (array_key_exists($likely, $arr) && $arr[$likely] !== null) {
                return [$likely => $arr[$likely]];
            }
        }

        // Fallback (laatste redmiddel)
        $where = [];
        foreach ($arr as $k => $v) {
            if (is_resource($v)) {
                continue;
            }
            $where[$k] = $v;
        }

        return $where;
    }

    /* -----------------------------------------------------------------------------
     | Per-row / per-column transform (ONLY strings with data-youtube-video)
     * ---------------------------------------------------------------------------*/

    public function processRowColumns(object $row, array $columns): array
    {
        $updates = [];

        foreach ($columns as $col) {
            $value = $row->$col ?? null;
            if ($value === null) {
                continue;
            }

            // A) String value (kan HTML of JSON-string zijn)
            if (is_string($value)) {
                // Probeer als JSON (complexe struct)
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $changed = false;
                    $decoded = $this->transformRecursive($decoded, $changed);

                    if ($changed) {
                        $updates[$col] = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                } else {
                    // Plain string: alleen ingrijpen als data-youtube-video aanwezig is
                    if (stripos($value, 'data-youtube-video') !== false) {
                        $doc = $this->youtubeHtmlToTiptapDoc($value);
                        if ($doc) {
                            // Hele kolom wordt een doc
                            $updates[$col] = json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                    }
                }

                continue;
            }

            // B) Array/object (al gecast via Eloquent bijvoorbeeld)
            if (is_array($value) || is_object($value)) {
                $arr = json_decode(json_encode($value), true);
                $changed = false;
                $arr = $this->transformRecursive($arr, $changed);

                if ($changed) {
                    $updates[$col] = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
        }

        return $updates;
    }

    /**
     * Recursief: vervang elke string die 'data-youtube-video' bevat
     * door een **Tiptap doc** (met 1 externalVideo node).
     * Zet $changed=true als er ergens iets vervangen is.
     */
    public function transformRecursive($data, bool &$changed)
    {
        if (is_array($data)) {
            $out = [];
            foreach ($data as $k => $v) {
                if (is_string($v) && stripos($v, 'data-youtube-video') !== false) {
                    $doc = $this->youtubeHtmlToTiptapDoc($v);
                    if ($doc) {
                        $out[$k] = $doc; // <-- schrijf doc weg
                        $changed = true;

                        continue;
                    }
                }

                if (is_array($v) || is_object($v)) {
                    $out[$k] = $this->transformRecursive($v, $changed);
                } else {
                    $out[$k] = $v;
                }
            }

            return $out;
        }

        if (is_object($data)) {
            $arr = json_decode(json_encode($data), true);

            return $this->transformRecursive($arr, $changed);
        }

        return $data; // primitives
    }

    /* -----------------------------------------------------------------------------
     | HTML → Tiptap **doc** (externalVideo)
     * ---------------------------------------------------------------------------*/

    /**
     * Verwacht HTML met data-youtube-video + <iframe>.
     * Retourneert een Tiptap **doc** met één externalVideo node, of null bij failure.
     */
    public function youtubeHtmlToTiptapDoc(string $html): ?array
    {
        $src = $this->extractFirstIframeSrc($html);
        if (! $src) {
            return null;
        }

        $ratio = $this->extractRatioFromHtml($html) ?? '16:9';
        $provider = $this->guessProviderFromUrl($src) ?? 'youtube';

        return [
            'type' => 'doc',
            'content' => [[
                'type' => 'externalVideo',
                'attrs' => [
                    'src' => $src,
                    'provider' => $provider,
                    'ratio' => $ratio,
                ],
            ]],
        ];
    }

    /* -----------------------------------------------------------------------------
     | Extractors / utils
     * ---------------------------------------------------------------------------*/

    protected function extractFirstIframeSrc(string $html): ?string
    {
        return preg_match('~<iframe[^>]+src=["\']([^"\']+)["\']~i', $html, $m) ? $m[1] : null;
    }

    protected function extractRatioFromHtml(string $html): ?string
    {
        if (preg_match('~aspect-ratio\s*:\s*([0-9.]+)\s*/\s*([0-9.]+)~i', $html, $m)) {
            return $this->normalizeRatioString((float)$m[1], (float)$m[2]);
        }

        if (preg_match('~width=["\']?(\d+)["\']?[^>]*height=["\']?(\d+)["\']?~i', $html, $m)) {
            return $this->normalizeRatioString((float)$m[1], (float)$m[2]);
        }

        return null;
    }

    protected function normalizeRatioString(float $w, float $h): string
    {
        if ($w <= 0 || $h <= 0) {
            return '16:9';
        }
        $scale = 10000;
        $iw = (int) round($w * $scale);
        $ih = (int) round($h * $scale);
        $g = $this->gcd($iw, $ih) ?: 1;

        return sprintf('%d:%d', (int)($iw / $g), (int)($ih / $g));
    }

    protected function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        if ($b === 0) {
            return $a ?: 1;
        }
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return max(1, $a);
    }

    protected function guessProviderFromUrl(string $url): ?string
    {
        if (preg_match('~youtube(?:-nocookie)?\.com|youtu\.be~i', $url)) {
            return 'youtube';
        }
        if (preg_match('~vimeo\.com~i', $url)) {
            return 'vimeo';
        }

        return null;
    }
}
