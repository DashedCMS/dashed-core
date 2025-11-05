<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;

class MigrateDatabaseToV4 extends Command
{
    protected $signature = 'dashed:migrate-database-to-v4';
    protected $description = 'Convert legacy HTML content to Filament RichContent arrays';

    /**
     * ✅ Pas deze arrays naar smaak aan
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
     | Table / schema helpers
     * ---------------------------------------------------------------------------*/

    /**
     * Haalt alle tabellen op en filtert direct de skip-lijst eruit.
     */
    public function getAllTables(): array
    {
        // Works for MySQL/MariaDB
        $rows = \DB::select('SHOW TABLES');
        $tables = [];
        foreach ($rows as $row) {
            $tables[] = array_values((array) $row)[0];
        }

        // Filter skip-lijst
        $tables = array_values(array_filter($tables, fn ($t) => ! $this->shouldSkipTable($t)));

        return $tables;
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
     * ✅ Alleen kolommen die:
     * - in de whitelist staan, én
     * - text-achtig type hebben
     */
    public function getProcessableColumns(string $table): array
    {
        $db = \DB::getDatabaseName();

        // Haal alleen kolommen op die in de whitelist zitten
        $rows = \DB::table('information_schema.COLUMNS')
            ->select('COLUMN_NAME', 'DATA_TYPE')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', $table)
            ->whereIn('COLUMN_NAME', $this->columnNameWhitelist)
            ->get();

        $processableTypes = ['json', 'text', 'mediumtext', 'longtext', 'varchar', 'char'];

        $processable = [];
        foreach ($rows as $col) {
            $type = strtolower($col->DATA_TYPE);
            if (in_array($type, $processableTypes, true)) {
                $processable[] = $col->COLUMN_NAME;
            }
        }

        return $processable;
    }

    /* -----------------------------------------------------------------------------
     | Processing (by chunks)
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
            }, $column = $pk);
    }

    public function processTableByOffset(string $table, array $columns): void
    {
        $count = \DB::table($table)->count();
        $this->line("  Rows: {$count}; Columns: " . implode(', ', $columns));

        $offset = 0;
        $limit = 500;

        while (true) {
            $rows = \DB::table($table)
                ->select(['*']) // select all (no pk known)
                ->offset($offset)
                ->limit($limit)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                // try to find any unique-ish identifier to update by
                $where = $this->buildUniqueWhereFromRow($row);

                $updates = $this->processRowColumns($row, $columns);
                if (! empty($updates)) {
                    \DB::table($table)->where($where)->update($updates);
                }
            }

            $offset += $limit;
        }
    }

    /**
     * Build a best-effort WHERE from a row if no PK is known.
     * Prefer unique columns if present; fallback to all column values (not great, but works).
     */
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

        $where = [];
        foreach ($arr as $k => $v) {
            if (is_resource($v)) {
                continue;
            }
            $where[$k] = $v;
        }

        return $where;
    }

    /* -------------------------------------------------------------------------- */
    /* Per-row / per-column transform (ongewijzigd)                                */
    /* -------------------------------------------------------------------------- */

    public function processRowColumns(object $row, array $columns): array
    {
        $updates = [];
        foreach ($columns as $col) {
            $original = $row->$col ?? null;

            if ($original === null) {
                continue;
            }

            if (is_array($original) || is_object($original)) {
                $decoded = json_decode(json_encode($original), true);
                if (! is_array($decoded)) {
                    continue;
                }

                $converted = $this->walkArrayWithHtml($decoded, function ($html) {
                    return $this->htmlFragmentToRichContentDoc($html);
                });

                if ($decoded !== $converted) {
                    $updates[$col] = json_encode($converted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                continue;
            }

            if (is_string($original)) {
                $decoded = json_decode($original, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $converted = $this->walkArrayWithHtml($decoded, function ($html) {
                        return $this->htmlFragmentToRichContentDoc($html);
                    });

                    if ($decoded !== $converted) {
                        $updates[$col] = json_encode($converted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                } elseif ($this->containsHtml($original)) {
                    $convertedDoc = $this->htmlFragmentToRichContentDoc($original);
                    $updates[$col] = json_encode($convertedDoc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
        }

        return $updates;
    }

    /* -------------------------------------------------------------------------- */
    /* Recursive walker + HTML detection                                          */
    /* -------------------------------------------------------------------------- */

    public function walkArrayWithHtml(array $data, callable $callback, array $path = []): array
    {
        foreach ($data as $key => $value) {
            $currentPath = [...$path, $key];

            if (is_array($value)) {
                $data[$key] = $this->walkArrayWithHtml($value, $callback, $currentPath);
            } elseif (is_string($value) && $this->containsHtml($value)) {
                $data[$key] = $callback($value, $currentPath);
            }
        }

        return $data;
    }

    public function containsHtml(string $value): bool
    {
        return $value !== strip_tags($value);
    }

    /* -------------------------------------------------------------------------- */
    /* HTML → RichContent conversion (ongewijzigd)                                 */
    /* -------------------------------------------------------------------------- */

    public function htmlFragmentToRichContentDoc(string $html): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $nodes = [];
        foreach (iterator_to_array($dom->childNodes) as $child) {
            $nodes = array_merge($nodes, $this->convertDomNodeToTipTap($child));
        }

        if (empty($nodes)) {
            $nodes[] = ['type' => 'paragraph', 'attrs' => ['textAlign' => 'start']];
        }

        return ['type' => 'doc', 'content' => $nodes];
    }

    public function convertDomNodeToTipTap(\DOMNode $node): array
    {
        if ($node instanceof \DOMText) {
            $text = trim($node->nodeValue ?? '');

            return $text !== ''
                ? [[
                    'type' => 'paragraph',
                    'attrs' => ['textAlign' => 'start'],
                    'content' => [[ 'type' => 'text', 'text' => $text ]],
                ]]
                : [];
        }

        if (! ($node instanceof \DOMElement)) {
            return [];
        }

        $tag = strtolower($node->tagName);

        // TABLES
        if ($tag === 'table') {
            return [ $this->convertTableNode($node) ];
        }

        // VIDEO
        if ($tag === 'iframe' || ($tag === 'div' && $node->getElementsByTagName('iframe')->length)) {
            return [ $this->convertVideoNode($node) ];
        }

        // IMAGES
        if ($tag === 'img') {
            return [ $this->convertImageNode($node) ];
        }

        // BR
        if ($tag === 'br') {
            return [[
                'type' => 'paragraph',
                'attrs' => ['textAlign' => 'start'],
                'content' => [[ 'type' => 'hardBreak' ]],
            ]];
        }

        // P
        if ($tag === 'p') {
            $inline = $this->convertInlineChildren($node);

            return [[
                'type' => 'paragraph',
                'attrs' => ['textAlign' => 'start'],
                'content' => $inline ?: [[ 'type' => 'text', 'text' => '' ]],
            ]];
        }

        // Fallback: children in volgorde
        $out = [];
        foreach (iterator_to_array($node->childNodes) as $child) {
            $out = array_merge($out, $this->convertDomNodeToTipTap($child));
        }

        return $out;
    }

    public function convertInlineChildren(\DOMElement $el): array
    {
        $parts = [];

        foreach (iterator_to_array($el->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                $txt = $child->nodeValue;
                if ($txt !== '') {
                    $parts[] = ['type' => 'text', 'text' => $txt];
                }
                continue;
            }

            if (! ($child instanceof \DOMElement)) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if ($tag === 'br') {
                $parts[] = ['type' => 'hardBreak'];
                continue;
            }

            // Basic inline fallback; breid uit met marks indien gewenst
            $inner = trim($child->textContent ?? '');
            if ($inner !== '') {
                $parts[] = ['type' => 'text', 'text' => $inner];
            }
        }

        return $parts;
    }

    /* --------------------------- VIDEO / IMAGE -------------------------------- */

    public function convertVideoNode(\DOMElement $node): array
    {
        $iframe = strtolower($node->tagName) === 'iframe'
            ? $node
            : $node->getElementsByTagName('iframe')->item(0);

        $wrapper = strtolower($node->tagName) === 'iframe'
            ? ($node->parentNode instanceof \DOMElement ? $node->parentNode : null)
            : $node;

        $iframeAttrsAll = $iframe ? $this->extractAttributes($iframe) : [];
        $wrapperAttrsAll = $wrapper ? $this->extractAttributes($wrapper) : [];

        $embed = (string) ($iframeAttrsAll['src'] ?? '');
        [$type, $original] = $this->detectProviderAndOriginalUrl($embed);

        $ratio = $iframe ? ($this->extractRatioFromElement($iframe) ?? '16:9') : '16:9';
        if (! $ratio && $wrapper) {
            $ratio = $this->extractRatioFromElement($wrapper) ?? '16:9';
        }

        [$maxWidth, $unit] = $wrapper ? ($this->extractMaxWidthFromElement($wrapper) ?? ['100', '%']) : ['100', '%'];

        return [
            'type' => 'externalVideo',
            'attrs' => [
                'src' => $original ?: $embed,
                'type' => $wrapperAttrsAll['data-type'] ?? $type,
                'ratio' => $wrapperAttrsAll['data-ratio'] ?? $ratio,
                'maxWidth' => $wrapperAttrsAll['data-max-width'] ?? $maxWidth,
                'widthUnit' => $wrapperAttrsAll['data-width-unit'] ?? $unit,
                'wrapperAttributes' => $wrapperAttrsAll,
                'iframeAttributes' => $iframeAttrsAll,
            ],
        ];
    }

    public function convertImageNode(\DOMElement $img): array
    {
        $attrsAll = $this->extractAttributes($img);

        return [
            'type' => 'image',
            'attrs' => [
                'src' => (string) ($attrsAll['src'] ?? ''),
                'alt' => (string) ($attrsAll['alt'] ?? ''),
                'title' => (string) ($attrsAll['title'] ?? ''),
                'width' => $attrsAll['width'] ?? null,
                'height' => $attrsAll['height'] ?? null,
                'class' => $attrsAll['class'] ?? null,
                'style' => $attrsAll['style'] ?? null,
                'htmlAttributes' => $attrsAll,
            ],
        ];
    }

    /* ------------------------------ TABLES ------------------------------------ */

    public function convertTableNode(\DOMElement $table): array
    {
        $content = [];

        // THEAD
        foreach (iterator_to_array($table->getElementsByTagName('thead')) as $thead) {
            if ($thead->parentNode !== $table) {
                continue;
            }
            $content = array_merge($content, $this->convertTableSection($thead, true));
        }

        // TBODY(ies)
        $hasTbody = false;
        foreach (iterator_to_array($table->getElementsByTagName('tbody')) as $tbody) {
            if ($tbody->parentNode !== $table) {
                continue;
            }
            $hasTbody = true;
            $content = array_merge($content, $this->convertTableSection($tbody, false));
        }

        // Directe TR's als er geen secties zijn
        if (! $hasTbody && ! $table->getElementsByTagName('thead')->length) {
            foreach (iterator_to_array($table->childNodes) as $child) {
                if ($child instanceof \DOMElement && strtolower($child->tagName) === 'tr') {
                    $content[] = $this->convertTableRow($child, false);
                }
            }
        }

        // TFOOT onderaan
        foreach (iterator_to_array($table->getElementsByTagName('tfoot')) as $tfoot) {
            if ($tfoot->parentNode !== $table) {
                continue;
            }
            $content = array_merge($content, $this->convertTableSection($tfoot, false));
        }

        if (empty($content)) {
            $content[] = [
                'type' => 'tableRow',
                'content' => [
                    [
                        'type' => 'tableCell',
                        'attrs' => ['colspan' => 1,'rowspan' => 1,'colwidth' => null],
                        'content' => [[ 'type' => 'paragraph', 'attrs' => ['textAlign' => 'start'] ]],
                    ],
                ],
            ];
        }

        // ⚠️ Geen attrs op table (schema is strict)
        return [
            'type' => 'table',
            'content' => $content,
        ];
    }

    public function convertTableSection(\DOMElement $section, bool $asHeader): array
    {
        $rows = [];
        foreach (iterator_to_array($section->childNodes) as $child) {
            if ($child instanceof \DOMElement && strtolower($child->tagName) === 'tr') {
                $rows[] = $this->convertTableRow($child, $asHeader);
            }
        }

        return $rows;
    }

    public function convertTableRow(\DOMElement $tr, bool $asHeader): array
    {
        $cells = [];
        foreach (iterator_to_array($tr->childNodes) as $cell) {
            if (! ($cell instanceof \DOMElement)) {
                continue;
            }
            $tag = strtolower($cell->tagName);
            if ($tag === 'th' || $tag === 'td') {
                $cells[] = $this->convertTableCell($cell, $asHeader || $tag === 'th');
            }
        }

        if (empty($cells)) {
            $cells[] = [
                'type' => 'tableCell',
                'attrs' => ['colspan' => 1,'rowspan' => 1,'colwidth' => null],
                'content' => [[ 'type' => 'paragraph', 'attrs' => ['textAlign' => 'start'] ]],
            ];
        }

        return [
            'type' => 'tableRow',
            'content' => $cells,
        ];
    }

    public function convertTableCell(\DOMElement $cell, bool $isHeader): array
    {
        $attrsAll = $this->extractAttributes($cell);

        $colspan = (int) ($attrsAll['colspan'] ?? 1);
        $rowspan = (int) ($attrsAll['rowspan'] ?? 1);

        $content = $this->convertBlockyChildrenForCell($cell);
        if (empty($content)) {
            $content = [[ 'type' => 'paragraph', 'attrs' => ['textAlign' => 'start'] ]];
        }

        return [
            'type' => $isHeader ? 'tableHeader' : 'tableCell',
            'attrs' => [
                'colspan' => max(1, $colspan),
                'rowspan' => max(1, $rowspan),
                'colwidth' => null, // ✅ strikt volgens schema
            ],
            'content' => $content,
        ];
    }

    public function convertBlockyChildrenForCell(\DOMElement $cell): array
    {
        $blocks = [];

        foreach (iterator_to_array($cell->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                $txt = trim($child->nodeValue ?? '');
                if ($txt !== '') {
                    $blocks[] = [
                        'type' => 'paragraph',
                        'attrs' => ['textAlign' => 'start'],
                        'content' => [[ 'type' => 'text', 'text' => $txt ]],
                    ];
                }
                continue;
            }

            if (! ($child instanceof \DOMElement)) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if ($tag === 'p') {
                $inline = $this->convertInlineChildren($child);
                $blocks[] = [
                    'type' => 'paragraph',
                    'attrs' => ['textAlign' => 'start'],
                    'content' => $inline ?: [[ 'type' => 'text', 'text' => '' ]],
                ];
                continue;
            }

            if ($tag === 'img') {
                $blocks[] = $this->convertImageNode($child);
                continue;
            }

            if (in_array($tag, ['h1','h2','h3','h4','h5','h6'])) {
                $level = (int) substr($tag, 1);
                $text = trim($child->textContent ?? '');
                $blocks[] = [
                    'type' => 'heading',
                    'attrs' => ['level' => max(1, min(6, $level))],
                    'content' => $text !== '' ? [[ 'type' => 'text', 'text' => $text ]] : [],
                ];
                continue;
            }

            if (in_array($tag, ['ul','ol'])) {
                $listItems = [];
                foreach (iterator_to_array($child->childNodes) as $li) {
                    if ($li instanceof \DOMElement && strtolower($li->tagName) === 'li') {
                        $inline = $this->convertInlineChildren($li);
                        $listItems[] = [
                            'type' => 'listItem',
                            'content' => [[
                                'type' => 'paragraph',
                                'attrs' => ['textAlign' => 'start'],
                                'content' => $inline ?: [[ 'type' => 'text', 'text' => '' ]],
                            ]],
                        ];
                    }
                }
                $blocks[] = [
                    'type' => $tag === 'ul' ? 'bulletList' : 'orderedList',
                    'content' => $listItems,
                ];
                continue;
            }

            if ($tag === 'br') {
                $blocks[] = [
                    'type' => 'paragraph',
                    'attrs' => ['textAlign' => 'start'],
                    'content' => [[ 'type' => 'hardBreak' ]],
                ];
                continue;
            }

            // Fallback
            $text = trim($child->textContent ?? '');
            if ($text !== '') {
                $blocks[] = [
                    'type' => 'paragraph',
                    'attrs' => ['textAlign' => 'start'],
                    'content' => [[ 'type' => 'text', 'text' => $text ]],
                ];
            }
        }

        return $blocks;
    }

    /* -------------------------------------------------------------------------- */
    /* Helpers                                                                    */
    /* -------------------------------------------------------------------------- */

    public function extractAttributes(\DOMElement $el): array
    {
        $out = [];
        foreach ($el->attributes as $attr) {
            $out[$attr->name] = $attr->value;
        }

        return $out;
    }

    public function detectProviderAndOriginalUrl(string $embedUrl): array
    {
        $u = $embedUrl;

        if (preg_match('~youtube(?:-nocookie)?\.com/embed/([^?&/]+)~i', $u, $m)) {
            $id = $m[1];

            return ['youtube', "https://www.youtube.com/watch?v={$id}"];
        }
        if (preg_match('~youtu\.be/([^?&/]+)~i', $u, $m)) {
            $id = $m[1];

            return ['youtube', "https://www.youtube.com/watch?v={$id}"];
        }
        if (preg_match('~player\.vimeo\.com/video/(\d+)~i', $u, $m)) {
            $id = $m[1];

            return ['vimeo', "https://vimeo.com/{$id}"];
        }
        if (preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $u)) {
            return ['mp4', $u];
        }

        return ['auto', $u];
    }

    public function extractRatioFromElement(?\DOMElement $el): ?string
    {
        if (! $el instanceof \DOMElement) {
            return null;
        }

        $style = (string) ($el->getAttribute('style') ?? '');
        if ($style !== '' && preg_match('~aspect-ratio\s*:\s*([0-9.]+)\s*/\s*([0-9.]+)~i', $style, $m)) {
            [$iw, $ih] = $this->normalizeRatio((float) $m[1], (float) $m[2]);

            return "{$iw}:{$ih}";
        }

        $w = (float) ($el->getAttribute('width') ?: 0);
        $h = (float) ($el->getAttribute('height') ?: 0);
        if ($w > 0 && $h > 0) {
            [$iw, $ih] = $this->normalizeRatio($w, $h);

            return "{$iw}:{$ih}";
        }

        return null;
    }

    public function extractMaxWidthFromElement(?\DOMElement $el): ?array
    {
        if (! $el instanceof \DOMElement) {
            return null;
        }

        $style = (string) ($el->getAttribute('style') ?? '');
        if ($style !== '' && preg_match('~max-width\s*:\s*([0-9.]+)\s*(px|%)~i', $style, $m)) {
            return [$m[1], $m[2]];
        }

        return null;
    }

    public function normalizeRatio(float $w, float $h): array
    {
        if ($w <= 0 || $h <= 0) {
            return [16, 9];
        }
        $scale = 10000;
        $iw = (int) round($w * $scale);
        $ih = (int) round($h * $scale);
        $g = $this->gcd($iw, $ih);

        return [$iw / $g, $ih / $g];
    }

    public function gcd(int $a, int $b): int
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
}
